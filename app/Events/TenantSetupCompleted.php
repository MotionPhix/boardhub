<?php

namespace App\Events;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\WelcomeToOrganizationNotification;
use Thunk\Verbs\Attributes\StateId;
use Thunk\Verbs\Event;

class TenantSetupCompleted extends Event
{
    #[StateId(TenantState::class)]
    public int $tenant_id;

    public string $completion_trigger; // What caused completion (e.g., 'business_info_added', 'owner_assigned')

    public function handle()
    {
        $tenant = Tenant::find($this->tenant_id);

        if (!$tenant) {
            return;
        }

        // Update activated timestamp if not already set
        if (!$tenant->activated_at) {
            $tenant->update(['activated_at' => now()]);
        }

        // Send welcome notifications to all owners
        $owners = $tenant->owners()->get();
        foreach ($owners as $owner) {
            $owner->notify(new WelcomeToOrganizationNotification($tenant));
        }

        // Log the completion event
        logger()->info('Organization setup completed', [
            'tenant_id' => $this->tenant_id,
            'tenant_name' => $tenant->name,
            'completion_trigger' => $this->completion_trigger,
            'owners_count' => $owners->count(),
        ]);

        // Fire additional events for onboarding workflow
        TenantOnboardingStarted::fire(
            tenant_id: $this->tenant_id,
            onboarding_step: 'welcome'
        );
    }
}
