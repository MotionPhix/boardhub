<?php

namespace App\Observers;

use App\Models\Tenant;

class TenantObserver
{
    /**
     * Handle the Tenant "created" event.
     */
    public function created(Tenant $tenant): void
    {
        // Check setup completion on creation
        $this->checkSetupCompletion($tenant);
    }

    /**
     * Handle the Tenant "updated" event.
     */
    public function updated(Tenant $tenant): void
    {
        // Check setup completion on update
        $this->checkSetupCompletion($tenant);
    }

    /**
     * Check and update setup completion status
     */
    private function checkSetupCompletion(Tenant $tenant): void
    {
        // Get the fields that affect setup completion
        $setupRelevantFields = [
            'name', 'slug', 'business_type', 'industry', 'contact_info', 'subscription_tier'
        ];

        // Check if any setup-relevant field was changed
        $hasRelevantChanges = collect($setupRelevantFields)->some(function ($field) use ($tenant) {
            return $tenant->wasChanged($field);
        });

        // If relevant fields changed or this is a new tenant, check setup completion
        if ($hasRelevantChanges || $tenant->wasRecentlyCreated) {
            // Prevent infinite recursion by checking if setup_completed needs updating
            $wasCompleted = $tenant->getOriginal('setup_completed', false);
            $isComplete = $tenant->isSetupComplete();

            if ($tenant->setup_completed !== $isComplete) {
                // Update without triggering observers again
                $tenant->updateQuietly(['setup_completed' => $isComplete]);

                // Fire Verbs event when setup becomes complete
                if (!$wasCompleted && $isComplete) {
                    $trigger = $this->determineCompletionTrigger($tenant);
                    \App\Events\TenantSetupCompleted::fire(
                        tenant_id: $tenant->id,
                        completion_trigger: $trigger
                    );
                }
            }
        }
    }

    /**
     * Determine what field change triggered completion
     */
    private function determineCompletionTrigger(Tenant $tenant): string
    {
        $changed = array_keys($tenant->getChanges());

        if (in_array('business_type', $changed) || in_array('industry', $changed)) {
            return 'business_info_completed';
        }

        if (in_array('contact_info', $changed)) {
            return 'contact_info_added';
        }

        if (in_array('subscription_tier', $changed)) {
            return 'subscription_updated';
        }

        return 'general_completion';
    }
}
