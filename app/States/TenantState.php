<?php

namespace App\States;

use Thunk\Verbs\State;

class TenantState extends State
{
    public string $name;
    public ?string $business_type = null;
    public ?string $industry = null;
    public ?string $company_size = null;
    public array $contact_info = [];
    public string $subscription_tier = 'trial';
    public array $feature_flags = [];
    public array $onboarding_progress = [];
    public int $onboarding_completion = 0;
    public bool $setup_completed = false;
    public bool $is_active = true;
    public ?string $activated_at = null;
    public ?string $created_at = null;
    public ?string $trial_ends_at = null;

    // Analytics
    public int $total_bookings = 0;
    public float $total_revenue = 0.0;
    public float $monthly_revenue = 0.0;
    public array $usage_stats = [];

    // Branding
    public ?string $logo_url = null;
    public string $primary_color = '#6366f1';
    public string $secondary_color = '#8b5cf6';
    public array $branding_settings = [];

    // Limits and quotas
    public int $user_limit = 5;
    public int $billboard_limit = 100;

    // Compliance and security
    public array $compliance_settings = [];
    public string $data_region = 'mw';
    public bool $requires_2fa = false;
}