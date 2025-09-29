<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Membership;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TestOnboardingFlow extends Command
{
    protected $signature = 'test:onboarding {--reset : Reset and recreate test organization}';
    protected $description = 'Test the complete onboarding flow with a sample organization';

    public function handle()
    {
        $this->info('🚀 Testing Onboarding Flow');
        $this->newLine();

        // Clean up previous test if reset flag is used
        if ($this->option('reset')) {
            $this->info('🧹 Cleaning up previous test organizations...');
            Tenant::where('name', 'LIKE', 'Test Billboard Agency%')->delete();
        }

        // Step 1: Create test organization with minimal data
        $this->info('📋 Step 1: Creating test organization with minimal data');
        $tenant = $this->createTestOrganization();
        $this->displayTenantStatus($tenant, 'Initial State');

        // Step 2: Add owner (should still be incomplete - missing business info)
        $this->info('👤 Step 2: Adding owner to organization');
        $owner = $this->addOwnerToOrganization($tenant);
        $tenant = $tenant->fresh();
        $this->displayTenantStatus($tenant, 'After Adding Owner');

        // Step 3: Add business information (this should trigger completion!)
        $this->info('🏢 Step 3: Adding business information');
        $this->addBusinessInformation($tenant);
        $tenant = $tenant->fresh();
        $this->displayTenantStatus($tenant, 'After Adding Business Info');

        // Step 4: Add contact information (should maintain completion)
        $this->info('📞 Step 4: Adding contact information');
        $this->addContactInformation($tenant);
        $tenant = $tenant->fresh();
        $this->displayTenantStatus($tenant, 'After Adding Contact Info');

        // Step 5: Test branding (optional step)
        $this->info('🎨 Step 5: Adding branding');
        $this->addBranding($tenant);
        $tenant = $tenant->fresh();
        $this->displayTenantStatus($tenant, 'After Adding Branding');

        $this->newLine();
        $this->info('✅ Onboarding flow test completed!');
        $this->info("🔗 Test Organization ID: {$tenant->id}");
        $this->info("🔗 Test Organization UUID: {$tenant->uuid}");

        return 0;
    }

    private function createTestOrganization(): Tenant
    {
        $tenant = Tenant::create([
            'uuid' => Str::uuid(),
            'name' => 'Test Billboard Agency ' . now()->format('Y-m-d H:i:s'),
            'slug' => 'test-billboard-agency-' . time(),
            'is_active' => true,
            'subscription_tier' => 'trial',
            'trial_ends_at' => now()->addDays(30),
            'setup_completed' => false,
        ]);

        $this->line("   Created: {$tenant->name} (ID: {$tenant->id})");
        return $tenant;
    }

    private function addOwnerToOrganization(Tenant $tenant): User
    {
        // Create or find a test user
        $user = User::firstOrCreate(
            ['email' => 'test-owner@example.com'],
            [
                'name' => 'Test Owner',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Add user as owner
        $membership = Membership::firstOrCreate([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ], [
            'role' => Membership::ROLE_OWNER,
            'status' => Membership::STATUS_ACTIVE,
            'joined_at' => now(),
            'last_accessed_at' => now(),
        ]);

        $this->line("   Added owner: {$user->name} ({$user->email})");
        return $user;
    }

    private function addBusinessInformation(Tenant $tenant): void
    {
        $tenant->update([
            'business_type' => 'advertising_agency',
            'industry' => 'advertising_marketing',
            'company_size' => 'small',
        ]);

        $this->line('   Added: business_type, industry, company_size');
    }

    private function addContactInformation(Tenant $tenant): void
    {
        $tenant->update([
            'contact_info' => [
                'phone' => '+260 123 456 789',
                'address' => '123 Business Street, Lusaka',
                'city' => 'Lusaka',
                'country' => 'Zambia',
                'website' => 'https://testbillboardagency.com',
            ]
        ]);

        $this->line('   Added: contact information');
    }

    private function addBranding(Tenant $tenant): void
    {
        $tenant->update([
            'primary_color' => '#3b82f6',
            'secondary_color' => '#06b6d4',
            'logo_url' => 'https://via.placeholder.com/200x100/3b82f6/ffffff?text=Test+Agency',
        ]);

        $this->line('   Added: branding colors and logo');
    }

    private function displayTenantStatus(Tenant $tenant, string $stage): void
    {
        $criteria = $tenant->getSetupCompletionCriteria();
        $status = $tenant->status;

        $this->newLine();
        $this->line("   📊 <info>{$stage}</info>");
        $this->line("   Setup Complete: " . ($tenant->setup_completed ? '✅ Yes' : '❌ No'));
        $this->line("   Status: <comment>{$status->value}</comment>");
        $this->line("   Criteria:");

        foreach ($criteria as $key => $met) {
            $icon = $met ? '✅' : '❌';
            $this->line("     {$icon} {$key}");
        }

        $this->newLine();
    }
}
