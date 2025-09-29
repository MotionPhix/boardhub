<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = [
            [
                'name' => 'Acme Advertising Agency',
                'slug' => 'acme-advertising',
                'subdomain' => 'acme',
                'settings' => [
                    'company_registration' => 'BRS-2023-001',
                    'contact_person' => 'John Banda',
                    'phone' => '+265 1 234 567',
                    'address' => 'Area 47, Lilongwe',
                ],
            ],
            [
                'name' => 'Malawi Marketing Solutions',
                'slug' => 'malawi-marketing',
                'subdomain' => 'mms',
                'settings' => [
                    'company_registration' => 'BRS-2023-002',
                    'contact_person' => 'Grace Phiri',
                    'phone' => '+265 1 345 678',
                    'address' => 'Victoria Avenue, Blantyre',
                ],
            ],
            [
                'name' => 'Northern Media Group',
                'slug' => 'northern-media',
                'subdomain' => 'northern',
                'settings' => [
                    'company_registration' => 'BRS-2023-003',
                    'contact_person' => 'Peter Mwale',
                    'phone' => '+265 1 456 789',
                    'address' => 'Mzuzu City Center',
                ],
            ],
        ];

        foreach ($tenants as $tenantData) {
            // Use updateOrCreate so running seeds multiple times won't break on unique constraints
            Tenant::updateOrCreate([
                'slug' => $tenantData['slug'],
            ], $tenantData);
        }

        // Output tenant UUIDs for reference
        $this->command->info('Created or updated tenants with UUIDs:');
        Tenant::all()->each(function ($tenant) {
            $this->command->info("- {$tenant->name}: {$tenant->uuid}");
        });
    }
}
