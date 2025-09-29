<?php

namespace App\Observers;

use App\Models\Membership;

class MembershipObserver
{
    /**
     * Handle the Membership "created" event.
     */
    public function created(Membership $membership): void
    {
        $this->updateTenantSetupStatus($membership);
    }

    /**
     * Handle the Membership "updated" event.
     */
    public function updated(Membership $membership): void
    {
        $this->updateTenantSetupStatus($membership);
    }

    /**
     * Handle the Membership "deleted" event.
     */
    public function deleted(Membership $membership): void
    {
        $this->updateTenantSetupStatus($membership);
    }

    /**
     * Update tenant setup completion when membership changes
     */
    private function updateTenantSetupStatus(Membership $membership): void
    {
        if ($membership->tenant) {
            $membership->tenant->updateSetupCompletionStatus();
        }
    }
}
