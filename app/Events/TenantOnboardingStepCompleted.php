<?php

namespace App\Events;

use App\States\TenantState;
use Thunk\Verbs\Attributes\StateId;
use Thunk\Verbs\Event;

class TenantOnboardingStepCompleted extends Event
{
    #[StateId(TenantState::class)]
    public int $tenant_id;

    public function __construct(
        int $tenant_id,
        public string $step,
        public array $step_data = [],
    ) {
        $this->tenant_id = $tenant_id;
    }

    public function apply(TenantState $state): void
    {
        $progress = $state->onboarding_progress ?? [];
        $progress[$this->step] = true;
        $progress["{$this->step}_completed_at"] = now()->toISOString();

        if (!empty($this->step_data)) {
            $progress["{$this->step}_data"] = $this->step_data;
        }

        $state->onboarding_progress = $progress;

        // Calculate completion percentage
        $defaultSteps = \App\Models\Tenant::getDefaultOnboardingProgress();
        $completed = count(array_filter($progress, fn($value, $key) =>
            is_bool($value) && $value && array_key_exists($key, $defaultSteps)
        , ARRAY_FILTER_USE_BOTH));

        $state->onboarding_completion = (int) (($completed / count($defaultSteps)) * 100);

        // Mark as setup completed if 80% or more done
        if ($state->onboarding_completion >= 80 && !$state->setup_completed) {
            $state->setup_completed = true;
            $state->activated_at = now();
        }
    }

    public function handle(): void
    {
        $tenant = \App\Models\Tenant::find($this->tenant_id);
        if ($tenant) {
            $tenant->completeOnboardingStep($this->step);

            // Update additional data if provided
            if (!empty($this->step_data)) {
                $this->handleStepSpecificData($tenant);
            }
        }
    }

    private function handleStepSpecificData(\App\Models\Tenant $tenant): void
    {
        match ($this->step) {
            'profile_setup' => $this->handleProfileSetup($tenant),
            'branding_configured' => $this->handleBrandingSetup($tenant),
            'payment_configured' => $this->handlePaymentSetup($tenant),
            default => null,
        };
    }

    private function handleProfileSetup(\App\Models\Tenant $tenant): void
    {
        $tenant->update([
            'business_type' => $this->step_data['business_type'] ?? $tenant->business_type,
            'industry' => $this->step_data['industry'] ?? $tenant->industry,
            'company_size' => $this->step_data['company_size'] ?? $tenant->company_size,
            'contact_info' => array_merge($tenant->contact_info ?? [], $this->step_data['contact_info'] ?? []),
        ]);
    }

    private function handleBrandingSetup(\App\Models\Tenant $tenant): void
    {
        $tenant->update([
            'primary_color' => $this->step_data['primary_color'] ?? $tenant->primary_color,
            'secondary_color' => $this->step_data['secondary_color'] ?? $tenant->secondary_color,
            'logo_url' => $this->step_data['logo_url'] ?? $tenant->logo_url,
            'branding_settings' => array_merge($tenant->branding_settings ?? [], $this->step_data['branding_settings'] ?? []),
        ]);
    }

    private function handlePaymentSetup(\App\Models\Tenant $tenant): void
    {
        // Handle payment configuration
        // This would integrate with payment providers
    }

    public function fired(): void
    {
        // Send congratulations for major milestones
        if (in_array($this->step, ['first_booking_created', 'payment_configured'])) {
            TenantMilestoneReached::fire(
                tenant_id: $this->tenant_id,
                milestone: $this->step
            );
        }
    }
}