<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'domain' => $this->faker->optional()->domainName(),
            'subdomain' => $this->faker->optional()->domainWord(),
            'settings' => [
                'timezone' => $this->faker->timezone(),
                'currency' => 'USD',
                'date_format' => 'Y-m-d',
            ],
            'is_active' => true,
            'trial_ends_at' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
            'subscription_ends_at' => $this->faker->optional()->dateTimeBetween('+1 month', '+1 year'),
            'logo_url' => $this->faker->optional()->imageUrl(),
            'primary_color' => $this->faker->hexColor(),
            'secondary_color' => $this->faker->hexColor(),
            'branding_settings' => Tenant::getDefaultBrandingSettings(),
            'business_type' => $this->faker->randomElement([
                Tenant::BUSINESS_AGENCY,
                Tenant::BUSINESS_BILLBOARD_OWNER,
                Tenant::BUSINESS_HYBRID,
            ]),
            'industry' => $this->faker->randomElement([
                'Advertising',
                'Marketing',
                'Real Estate',
                'Retail',
                'Entertainment',
                'Transportation',
            ]),
            'company_size' => $this->faker->randomElement(['1-10', '11-50', '51-200', '201-500', '500+']),
            'contact_info' => [
                'phone' => $this->faker->phoneNumber(),
                'email' => $this->faker->companyEmail(),
                'address' => $this->faker->address(),
                'website' => $this->faker->optional()->url(),
            ],
            'subscription_tier' => $this->faker->randomElement([
                Tenant::TIER_TRIAL,
                Tenant::TIER_STARTER,
                Tenant::TIER_PROFESSIONAL,
                Tenant::TIER_ENTERPRISE,
            ]),
            'feature_flags' => Tenant::getDefaultFeatureFlags(),
            'monthly_revenue' => $this->faker->randomFloat(2, 0, 100000),
            'user_limit' => $this->faker->numberBetween(5, 100),
            'billboard_limit' => $this->faker->numberBetween(10, 1000),
            'usage_stats' => [
                'users_count' => $this->faker->numberBetween(0, 10),
                'billboards_count' => $this->faker->numberBetween(0, 50),
                'active_bookings' => $this->faker->numberBetween(0, 25),
                'monthly_bookings' => $this->faker->numberBetween(0, 100),
                'last_updated' => now()->toISOString(),
            ],
            'last_activity_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'total_bookings' => $this->faker->numberBetween(0, 500),
            'total_revenue' => $this->faker->randomFloat(2, 0, 500000),
            'compliance_settings' => [
                'gdpr_enabled' => $this->faker->boolean(),
                'data_retention_days' => $this->faker->numberBetween(30, 365),
                'cookie_consent' => $this->faker->boolean(),
            ],
            'data_region' => $this->faker->randomElement(['us-east', 'us-west', 'eu-west', 'ap-southeast']),
            'requires_2fa' => $this->faker->boolean(30),
            'onboarding_progress' => Tenant::getDefaultOnboardingProgress(),
            'setup_completed' => $this->faker->boolean(80),
            'activated_at' => $this->faker->optional(80)->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the tenant is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'setup_completed' => true,
            'activated_at' => now(),
        ]);
    }

    /**
     * Indicate that the tenant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the tenant is on trial.
     */
    public function trial(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_tier' => Tenant::TIER_TRIAL,
            'trial_ends_at' => now()->addDays(30),
            'setup_completed' => true,
        ]);
    }

    /**
     * Indicate that the tenant setup is incomplete.
     */
    public function setupIncomplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'setup_completed' => false,
            'activated_at' => null,
        ]);
    }
}
