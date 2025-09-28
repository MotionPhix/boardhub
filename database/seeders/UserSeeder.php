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

        // Create super admin user (no tenant - has access to everything)
        $superAdmin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@adpro.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'tenant_id' => null, // Super admin belongs to no specific tenant
        ]);

        // Create tenant users with proper roles and memberships
        $tenantOwner = User::create([
            'name' => 'Tenant Owner',
            'email' => 'owner@adpro.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'tenant_id' => $firstTenant->id,
        ]);

        // Create membership for tenant owner
        Membership::create([
            'user_id' => $tenantOwner->id,
            'tenant_id' => $firstTenant->id,
            'role' => Membership::ROLE_OWNER,
            'status' => Membership::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        $tenantAdmin = User::create([
            'name' => 'Tenant Admin',
            'email' => 'tenant-admin@adpro.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'tenant_id' => $firstTenant->id,
        ]);

        // Create membership for tenant admin
        Membership::create([
            'user_id' => $tenantAdmin->id,
            'tenant_id' => $firstTenant->id,
            'role' => Membership::ROLE_ADMIN,
            'status' => Membership::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@adpro.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'tenant_id' => $firstTenant->id,
        ]);

        // Create membership for manager
        Membership::create([
            'user_id' => $manager->id,
            'tenant_id' => $firstTenant->id,
            'role' => Membership::ROLE_MANAGER,
            'status' => Membership::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        $member = User::create([
            'name' => 'Member User',
            'email' => 'member@adpro.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'tenant_id' => $firstTenant->id,
        ]);

        // Create membership for member
        Membership::create([
            'user_id' => $member->id,
            'tenant_id' => $firstTenant->id,
            'role' => Membership::ROLE_MEMBER,
            'status' => Membership::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        $viewer = User::create([
            'name' => 'Viewer User',
            'email' => 'viewer@adpro.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'tenant_id' => $firstTenant->id,
        ]);

        // Create membership for viewer
        Membership::create([
            'user_id' => $viewer->id,
            'tenant_id' => $firstTenant->id,
            'role' => Membership::ROLE_VIEWER,
            'status' => Membership::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        // Create users for second tenant if it exists
        if ($secondTenant) {
            $secondTenantOwner = User::create([
                'name' => 'Second Tenant Owner',
                'email' => 'owner2@adpro.test',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'tenant_id' => $secondTenant->id,
            ]);

            Membership::create([
                'user_id' => $secondTenantOwner->id,
                'tenant_id' => $secondTenant->id,
                'role' => Membership::ROLE_OWNER,
                'status' => Membership::STATUS_ACTIVE,
                'joined_at' => now(),
            ]);
        }

        // Create a few more random users for first tenant
        $randomUsers = User::factory(3)->create([
            'tenant_id' => $firstTenant->id,
        ]);

        // Create memberships for random users
        foreach ($randomUsers as $user) {
            Membership::create([
                'user_id' => $user->id,
                'tenant_id' => $firstTenant->id,
                'role' => Membership::ROLE_MEMBER,
                'status' => Membership::STATUS_ACTIVE,
                'joined_at' => now(),
            ]);
        }

        $this->command->info('Created users with proper roles:');
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
