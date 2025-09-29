<?php

namespace App\Events;

use App\States\TenantState;
use Thunk\Verbs\Attributes\StateId;
use Thunk\Verbs\Event;

class TenantOnboardingStarted extends Event
{
    #[StateId(TenantState::class)]
    public int $tenant_id;

    public string $onboarding_step;

    public function apply(TenantState $state)
    {
        // Initialize onboarding progress if not already set
        if (empty($state->onboarding_progress)) {
            $state->onboarding_progress = [
                'welcome' => false,
                'business_info' => false,
                'team_setup' => false,
                'branding' => false,
                'first_billboard' => false,
                'billing_setup' => false,
            ];
        }

        // Mark the current step as started
        $state->onboarding_progress[$this->onboarding_step] = true;

        // Calculate completion percentage
        $completedSteps = count(array_filter($state->onboarding_progress));
        $totalSteps = count($state->onboarding_progress);
        $state->onboarding_completion = round(($completedSteps / $totalSteps) * 100);
    }

    public function handle()
    {
        // Log onboarding start
        logger()->info('Tenant onboarding started', [
            'tenant_id' => $this->tenant_id,
            'onboarding_step' => $this->onboarding_step,
        ]);
    }
}
