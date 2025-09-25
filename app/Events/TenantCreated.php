<?php

namespace App\Events;

use App\Models\Tenant;
use App\States\TenantState;
use Thunk\Verbs\Attributes\StateId;
use Thunk\Verbs\Event;
use Illuminate\Support\Str;

class TenantCreated extends Event
{
    #[StateId(TenantState::class)]
    public ?int $tenant_id = null;

    public function __construct(
        public string $name,
        public string $email,
        public ?string $business_type = null,
        public ?string $industry = null,
        public ?string $company_size = null,
        public array $contact_info = [],
    ) {}

    public function apply(TenantState $state): void
    {
        $state->name = $this->name;
        $state->business_type = $this->business_type;
        $state->industry = $this->industry;
        $state->company_size = $this->company_size;
        $state->contact_info = $this->contact_info;
        $state->created_at = now();
        $state->subscription_tier = Tenant::TIER_TRIAL;
        $state->trial_ends_at = now()->addDays(14);
        $state->is_active = true;
    }

    public function handle(): Tenant
    {
        $tenant = Tenant::create([
            'id' => $this->tenant_id,
            'name' => $this->name,
            'slug' => Str::slug($this->name),
            'subdomain' => Str::slug($this->name),
            'business_type' => $this->business_type,
            'industry' => $this->industry,
            'company_size' => $this->company_size,
            'contact_info' => $this->contact_info,
            'subscription_tier' => Tenant::TIER_TRIAL,
            'trial_ends_at' => now()->addDays(14),
            'is_active' => true,
        ]);

        return $tenant;
    }

    public function fired(): void
    {
        // Fire welcome email event
        TenantWelcomeEmailSent::fire(
            tenant_id: $this->tenant_id,
            email: $this->email,
            name: $this->name
        );

        // Initialize default billboards for demo
        if ($this->business_type === Tenant::BUSINESS_AGENCY) {
            TenantDemoBillboardsCreated::fire(tenant_id: $this->tenant_id);
        }
    }
}