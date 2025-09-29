<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Membership;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get tenants for assignment (created by TenantSeeder)
        $tenants = Tenant::all();
        $firstTenant = $tenants->first();
        $secondTenant = $tenants->skip(1)->first();

        if (!$firstTenant) {
            $this->command->error('No tenants found! Run TenantSeeder first.');
            return;
        }

        // Create or update super admin user (no tenant - has access to everything)
        $superAdmin = User::updateOrCreate([
            'email' => 'admin@adpro.test',
        ], [
            'name' => 'System Administrator',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'tenant_id' => null, // Super admin belongs to no specific tenant
        ]);

        // Helper for user creation/update and membership
        $createUserWithMembership = function (array $userData, ?Tenant $tenant, string $role = Membership::ROLE_MEMBER) {
            $email = $userData['email'];

            $user = User::updateOrCreate([
                'email' => $email,
            ], [
                'name' => $userData['name'],
                'password' => $userData['password'] ?? Hash::make('password'),
                'email_verified_at' => $userData['email_verified_at'] ?? now(),
                'tenant_id' => $tenant?->id,
            ]);

            if ($tenant) {
                Membership::updateOrCreate([
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                ], [
                    'role' => $role,
                    'status' => Membership::STATUS_ACTIVE,
                    'joined_at' => now(),
                ]);
            }

            return $user;
        };

        // Create tenant users with proper roles and memberships
        $tenantOwner = $createUserWithMembership([
            'name' => 'Tenant Owner',
            'email' => 'owner@adpro.test',
        ], $firstTenant, Membership::ROLE_OWNER);

        $tenantAdmin = $createUserWithMembership([
            'name' => 'Tenant Admin',
            'email' => 'tenant-admin@adpro.test',
        ], $firstTenant, Membership::ROLE_ADMIN);

        $manager = $createUserWithMembership([
            'name' => 'Manager User',
            'email' => 'manager@adpro.test',
        ], $firstTenant, Membership::ROLE_MANAGER);

        $member = $createUserWithMembership([
            'name' => 'Member User',
            'email' => 'member@adpro.test',
        ], $firstTenant, Membership::ROLE_MEMBER);

        $viewer = $createUserWithMembership([
            'name' => 'Viewer User',
            'email' => 'viewer@adpro.test',
        ], $firstTenant, Membership::ROLE_VIEWER);

        // Create users for second tenant if it exists
        if ($secondTenant) {
            $secondTenantOwner = $createUserWithMembership([
                'name' => 'Second Tenant Owner',
                'email' => 'owner2@adpro.test',
            ], $secondTenant, Membership::ROLE_OWNER);
        }

        // Create a few more random users for first tenant
        $randomUsers = User::factory(3)->create([
            'tenant_id' => $firstTenant->id,
        ]);

        // Create memberships for random users (use updateOrCreate to avoid duplicates)
        foreach ($randomUsers as $user) {
            Membership::updateOrCreate([
                'user_id' => $user->id,
                'tenant_id' => $firstTenant->id,
            ], [
                'role' => Membership::ROLE_MEMBER,
                'status' => Membership::STATUS_ACTIVE,
                'joined_at' => now(),
            ]);
        }

        $this->command->info('Created or updated users with proper roles:');
        $this->command->info('- System Admin: admin@adpro.test / password (Super Admin)');
        $this->command->info('- Tenant Owner: owner@adpro.test / password (Owner)');
        $this->command->info('- Tenant Admin: tenant-admin@adpro.test / password (Admin)');
        $this->command->info('- Manager: manager@adpro.test / password (Manager)');
        $this->command->info('- Member: member@adpro.test / password (Member)');
        $this->command->info('- Viewer: viewer@adpro.test / password (Viewer)');
        if ($secondTenant) {
            $this->command->info('- Second Tenant Owner: owner2@adpro.test / password (Owner)');
        }
    }
}
