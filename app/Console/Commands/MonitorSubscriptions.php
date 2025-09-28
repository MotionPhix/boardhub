<?php

namespace App\Console\Commands;

use App\Events\SubscriptionExpired;
use App\Models\TenantSubscription;
use App\Services\SubscriptionService;
use App\States\SubscriptionState;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:monitor
                           {--dry-run : Show what would be done without making changes}
                           {--tenant= : Monitor specific tenant only}
                           {--notify-only : Only send notifications, no status changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor subscription statuses and handle expiration/payment issues';

    public function __construct(
        private SubscriptionService $subscriptionService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Starting subscription monitoring...');

        $dryRun = $this->option('dry-run');
        $tenantFilter = $this->option('tenant');
        $notifyOnly = $this->option('notify-only');

        if ($dryRun) {
            $this->warn('🚨 DRY RUN MODE - No changes will be made');
        }

        // Get subscriptions to monitor
        $subscriptions = $this->getSubscriptionsToMonitor($tenantFilter);

        $this->info("📊 Found {$subscriptions->count()} subscriptions to monitor");

        $stats = [
            'checked' => 0,
            'expired' => 0,
            'expiring_soon' => 0,
            'payment_issues' => 0,
            'notifications_sent' => 0,
            'actions_taken' => 0,
        ];

        foreach ($subscriptions as $subscription) {
            $stats['checked']++;
            $this->monitorSubscription($subscription, $dryRun, $notifyOnly, $stats);
        }

        $this->displaySummary($stats);

        return Command::SUCCESS;
    }

    private function getSubscriptionsToMonitor(?string $tenantFilter): \Illuminate\Database\Eloquent\Collection
    {
        $query = TenantSubscription::with(['tenant', 'billingPlan'])
            ->whereIn('status', ['active', 'trial', 'past_due']);

        if ($tenantFilter) {
            $query->whereHas('tenant', function($q) use ($tenantFilter) {
                $q->where('uuid', $tenantFilter)->orWhere('slug', $tenantFilter);
            });
        }

        return $query->get();
    }

    private function monitorSubscription(
        TenantSubscription $subscription,
        bool $dryRun,
        bool $notifyOnly,
        array &$stats
    ): void {
        $tenantName = $subscription->tenant->name ?? 'Unknown';
        $this->line("🔎 Checking: {$tenantName} ({$subscription->status})");

        // Check if subscription has expired
        if ($this->checkExpiration($subscription, $dryRun, $notifyOnly, $stats)) {
            return; // Skip other checks if expired
        }

        // Check if trial is ending soon
        if ($this->checkTrialExpiration($subscription, $dryRun, $stats)) {
            return;
        }

        // Check payment issues
        $this->checkPaymentIssues($subscription, $dryRun, $stats);

        // Check usage limits
        $this->checkUsageLimits($subscription, $stats);
    }

    private function checkExpiration(
        TenantSubscription $subscription,
        bool $dryRun,
        bool $notifyOnly,
        array &$stats
    ): bool {
        $isExpired = $subscription->isExpired();

        if (!$isExpired) {
            return false;
        }

        $stats['expired']++;
        $tenantName = $subscription->tenant->name;

        if ($subscription->status !== 'expired') {
            $this->warn("⏰ EXPIRED: {$tenantName} - subscription has expired");

            if (!$dryRun && !$notifyOnly) {
                // Fire expiration event
                SubscriptionExpired::fire(
                    subscription_data: [
                        'subscription_id' => $subscription->paychangu_subscription_id ?? $subscription->id,
                        'tenant_id' => $subscription->tenant_id,
                        'expiration_reason' => 'billing_cycle_ended',
                    ]
                );

                $stats['actions_taken']++;
                $this->info("✅ Fired expiration event for {$tenantName}");
            }
        }

        return true;
    }

    private function checkTrialExpiration(
        TenantSubscription $subscription,
        bool $dryRun,
        array &$stats
    ): bool {
        if ($subscription->status !== 'trial') {
            return false;
        }

        $daysUntilExpiration = $subscription->daysUntilExpiration();

        if ($daysUntilExpiration <= 7 && $daysUntilExpiration > 0) {
            $stats['expiring_soon']++;
            $tenantName = $subscription->tenant->name;

            $this->warn("⚡ EXPIRING SOON: {$tenantName} - trial ends in {$daysUntilExpiration} days");

            if (!$dryRun) {
                $this->sendTrialExpirationNotification($subscription, $daysUntilExpiration);
                $stats['notifications_sent']++;
            }

            return true;
        }

        return false;
    }

    private function checkPaymentIssues(
        TenantSubscription $subscription,
        bool $dryRun,
        array &$stats
    ): void {
        if (!$subscription->paychangu_subscription_id) {
            return;
        }

        $state = SubscriptionState::load($subscription->paychangu_subscription_id);

        if ($state->failed_payment_attempts > 0) {
            $stats['payment_issues']++;
            $tenantName = $subscription->tenant->name;

            $this->warn("💳 PAYMENT ISSUES: {$tenantName} - {$state->failed_payment_attempts} failed attempts");

            if (!$dryRun && $state->failed_payment_attempts === 3) {
                $this->sendPaymentIssueNotification($subscription, $state);
                $stats['notifications_sent']++;
            }
        }
    }

    private function checkUsageLimits(TenantSubscription $subscription, array &$stats): void
    {
        $plan = $subscription->billingPlan;
        if (!$plan) {
            return;
        }

        $tenantName = $subscription->tenant->name;
        $limitWarnings = [];

        foreach ($plan->features as $feature) {
            $limit = $this->subscriptionService->getFeatureLimit($subscription, $feature);
            if ($limit <= 0) continue; // Skip unlimited features

            $usage = $this->subscriptionService->getFeatureUsage($subscription, $feature);
            $percentage = ($usage / $limit) * 100;

            if ($percentage >= 90) {
                $limitWarnings[] = "{$feature}: {$usage}/{$limit} ({$percentage}%)";
            }
        }

        if (!empty($limitWarnings)) {
            $this->warn("📈 USAGE WARNING: {$tenantName}");
            foreach ($limitWarnings as $warning) {
                $this->line("   - {$warning}");
            }
        }
    }

    private function sendTrialExpirationNotification(
        TenantSubscription $subscription,
        int $daysRemaining
    ): void {
        \App\Events\RealTimeNotificationSent::fire(
            tenant_id: $subscription->tenant_id,
            type: 'trial_expiring',
            title: "Trial Expiring in {$daysRemaining} Days ⏰",
            message: "Your trial will expire soon. Upgrade now to continue using all features.",
            data: [
                'subscription_id' => $subscription->id,
                'days_remaining' => $daysRemaining,
                'trial_ends_at' => $subscription->trial_ends_at,
            ],
            priority: 'high'
        );
    }

    private function sendPaymentIssueNotification(
        TenantSubscription $subscription,
        SubscriptionState $state
    ): void {
        \App\Events\RealTimeNotificationSent::fire(
            tenant_id: $subscription->tenant_id,
            type: 'payment_retry_limit_reached',
            title: 'Payment Issues Detected 💳',
            message: 'Multiple payment attempts have failed. Please update your payment method to avoid service interruption.',
            data: [
                'subscription_id' => $subscription->id,
                'failed_attempts' => $state->failed_payment_attempts,
                'last_failed_at' => $state->last_failed_payment_at,
            ],
            priority: 'high'
        );
    }

    private function displaySummary(array $stats): void
    {
        $this->info('');
        $this->info('📋 MONITORING SUMMARY');
        $this->info('====================');
        $this->info("✅ Subscriptions checked: {$stats['checked']}");
        $this->info("⏰ Expired subscriptions: {$stats['expired']}");
        $this->info("⚡ Expiring soon: {$stats['expiring_soon']}");
        $this->info("💳 Payment issues: {$stats['payment_issues']}");
        $this->info("📬 Notifications sent: {$stats['notifications_sent']}");
        $this->info("🔧 Actions taken: {$stats['actions_taken']}");

        if ($stats['expired'] > 0 || $stats['payment_issues'] > 0) {
            $this->warn('⚠️  Issues found that may require attention!');
        } else {
            $this->info('🎉 All subscriptions are healthy!');
        }
    }
}