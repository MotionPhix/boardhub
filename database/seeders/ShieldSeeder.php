<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ShieldSeeder extends Seeder
{
  public function run(): void
  {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Clear existing roles and permissions
    Role::query()->delete();
    Permission::query()->delete();

    // Define all permissions
    $permissions = [
      // Billboard permissions
      'view_billboard',
      'view_any_billboard',
      'create_billboard',
      'update_billboard',
      'delete_billboard',
      'restore_billboard',
      'force_delete_billboard',

      // Location permissions
      'view_location',
      'view_any_location',
      'create_location',
      'update_location',
      'delete_location',
      'restore_location',
      'force_delete_location',

      // Contract permissions
      'view_contract',
      'view_any_contract',
      'create_contract',
      'update_contract',
      'delete_contract',
      'restore_contract',
      'force_delete_contract',
      'approve_contract',
      'reject_contract',

      // Contract permissions
      'view_contract::template',
      'view_any_contract::template',
      'create_contract::template',
      'update_contract::template',
      'delete_contract::template',
      'delete_any_contract::template',
      'force_delete_contract::template',
      'force_delete_any_contract::template',
      'restore_contract::template',
      'restore_any_contract::template',
      'replicate_contract::template',
      'reorder_contract::template',

      // User permissions
      'view_user',
      'view_any_user',
      'create_user',
      'update_user',

      // Shield (Roles) permissions
      'view_role',
      'view_any_role',
      'create_role',
      'update_role',
      'delete_role',

      // Widget permissions
      'view_StatsOverview',
      'view_ListRevenue',

      // Client permissions
      'view_client',
      'view_any_client',
      'create_client',
      'update_client',
      'delete_client',

      // Country permissions
      'view_country',
      'view_any_country',
      'create_country',
      'update_country',
      'delete_country',

      // State/Region permissions
      'view_state',
      'view_any_state',
      'create_state',
      'update_state',
      'delete_state',

      // City permissions
      'view_city',
      'view_any_city',
      'create_city',
      'update_city',
      'delete_city',

      // Settings permissions
      'view_settings',
      'view_any_settings',
      'create_settings',
      'update_settings',
      'delete_settings',

      // Add Currency permissions
      'view_currency',
      'view_any_currency',
      'create_currency',
      'update_currency',
      'delete_currency',
    ];

    // Create permissions
    foreach ($permissions as $permission) {
      Permission::create(['name' => $permission]);
    }

    $roles = [
      'super_admin' => [
        'description' => 'Full access to all features',
        // super_admin gets all permissions automatically
      ],
      'admin' => [
        'description' => 'Administrative access with some restrictions',
        'permissions' => [
          // Billboard permissions
          'view_billboard',
          'view_any_billboard',
          'create_billboard',
          'update_billboard',
          'delete_billboard',
          'restore_billboard',
          'force_delete_billboard',

          // Location permissions
          'view_location',
          'view_any_location',
          'create_location',
          'update_location',
          'delete_location',
          'restore_location',
          'force_delete_location',

          // Contract permissions
          'view_contract',
          'view_any_contract',
          'create_contract',
          'update_contract',
          'delete_contract',
          'restore_contract',
          'force_delete_contract',
          'approve_contract',
          'reject_contract',

          // Contract permissions
          'view_contract::template',
          'view_any_contract::template',
          'create_contract::template',
          'update_contract::template',
          'delete_contract::template',

          // User permissions
          'view_user',
          'view_any_user',
          'create_user',
          'update_user',

          // Shield (Roles) permissions
          'view_role',
          'view_any_role',
          'create_role',
          'update_role',
          'delete_role',

          // Widget permissions (for revenue information)
          'view_StatsOverview',
          'view_ListRevenue',

          // Client permissions
          'view_client',
          'view_any_client',
          'create_client',
          'update_client',
          'delete_client',

          // Country permissions
          'view_country',
          'view_any_country',
          'create_country',
          'update_country',
          'delete_country',

          // State/Region permissions
          'view_state',
          'view_any_state',
          'create_state',
          'update_state',
          'delete_state',

          // City permissions
          'view_city',
          'view_any_city',
          'create_city',
          'update_city',
          'delete_city',

          // Settings permissions
          'view_settings',
          'view_any_settings',
          'create_settings',
          'update_settings',
          'delete_settings',

          // Currency permissions
          'view_currency',
          'view_any_currency',
          'create_currency',
          'update_currency',
          'delete_currency',
        ]
      ],
      'manager' => [
        'description' => 'Manage billboards and contracts',
        'permissions' => [
          // Billboard permissions
          'view_billboard',
          'view_any_billboard',
          'create_billboard',
          'update_billboard',

          // Location permissions
          'view_location',
          'view_any_location',
          'create_location',
          'update_location',

          // Contract permissions
          'view_contract',
          'view_any_contract',
          'create_contract',
          'update_contract',
          'approve_contract',
          'reject_contract',

          // Client permissions
          'view_client',
          'view_any_client',
          'create_client',
          'update_client',

          // Basic user viewing permissions
          'view_user',
          'view_any_user',

          // Only view permissions for cities
          'view_city',
          'view_any_city',

          'view_state',
          'view_any_state',

          // Only view permissions for cities
          'view_country',
          'view_any_country',

          // Add view-only permissions for Settings and Currency
          'view_settings',
          'view_any_settings',
          'view_currency',
          'view_any_currency',
        ]
      ],
      'agent' => [
        'description' => 'Handle sales and contracts',
        'permissions' => [
          // Limited billboard and location viewing
          'view_billboard',
          'view_any_billboard',
          'view_location',
          'view_any_location',

          // Only view permissions for cities
          'view_city',
          'view_any_city',

          'view_state',
          'view_any_state',

          // Only view permissions for countries
          'view_country',
          'view_any_country',
        ]
      ],
    ];

    // Create roles and assign permissions
    foreach ($roles as $roleName => $roleData) {
      $role = Role::create([
        'name' => $roleName,
        'description' => $roleData['description'],
        'guard_name' => 'web',
      ]);

      if ($roleName === 'super_admin') {
        $role->givePermissionTo(Permission::all());
      } elseif (isset($roleData['permissions'])) {
        $role->givePermissionTo($roleData['permissions']);
      }
    }

    // Create users
    $users = [
      'super_admin' => [
        'name' => 'MotionPhix',
        'email' => 'boss@example.com',
      ],
      'admin' => [
        'name' => 'Admin User',
        'email' => 'admin@example.com',
      ],
      'manager' => [
        'name' => 'Manager User',
        'email' => 'manager@example.com',
      ],
      'agent' => [
        'name' => 'Agent User',
        'email' => 'agent@example.com',
      ],
    ];

    foreach ($users as $role => $userData) {
      $user = User::create([
        'name' => $userData['name'],
        'email' => $userData['email'],
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
        'is_active' => true,
        'created_at' => '2025-05-07 18:38:06',
      ]);

      $user->assignRole($role);
    }
  }
}
