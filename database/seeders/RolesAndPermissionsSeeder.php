<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
  public function run(): void
  {
    // Reset cached roles and permissions
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Create roles first
    $roles = [
      'super_admin' => [
        'description' => 'Full access to all features',
        'permissions' => [], // Will get all permissions
      ],
      'admin' => [
        'description' => 'Administrative access with some restrictions',
        'permissions' => [
          // Billboard permissions
          'view_any_billboard', 'view_billboard', 'create_billboard',
          'update_billboard', 'delete_billboard', 'restore_billboard',
          'force_delete_billboard',

          // Location permissions
          'view_any_location', 'view_location', 'create_location',
          'update_location', 'delete_location', 'restore_location',
          'force_delete_location',

          // Contract permissions
          'view_any_contract', 'view_contract', 'create_contract',
          'update_contract', 'delete_contract', 'restore_contract',
          'force_delete_contract', 'approve_contract', 'reject_contract',

          // Quote permissions
          'view_any_quote', 'view_quote', 'create_quote',
          'update_quote', 'delete_quote', 'approve_quote',
          'reject_quote',

          // User management
          'view_any_user', 'view_user', 'create_user',
          'update_user',

          // Additional permissions
          'manage_notification_settings',
        ],
      ],
      'manager' => [
        'description' => 'Manage billboards and contracts',
        'permissions' => [
          // Billboard permissions
          'view_any_billboard', 'view_billboard', 'create_billboard',
          'update_billboard', 'delete_billboard',

          // Location permissions
          'view_any_location', 'view_location', 'create_location',
          'update_location',

          // Contract permissions
          'view_any_contract', 'view_contract', 'create_contract',
          'update_contract', 'approve_contract', 'reject_contract',

          // Quote permissions
          'view_any_quote', 'view_quote', 'create_quote',
          'update_quote', 'approve_quote', 'reject_quote',

          // Basic user viewing
          'view_any_user', 'view_user',

          // Additional permissions
          'manage_notification_settings',
        ],
      ],
      'agent' => [
        'description' => 'Handle sales and contracts',
        'permissions' => [
          'view_any_billboard', 'view_billboard',
          'view_any_location', 'view_location',
          'view_any_contract', 'view_contract', 'create_contract',
          'view_any_quote', 'view_quote', 'create_quote', 'update_quote',
        ],
      ],
      'viewer' => [
        'description' => 'View-only access',
        'permissions' => [
          'view_any_billboard', 'view_billboard',
          'view_any_location', 'view_location',
        ],
      ],
    ];

    // Create all permissions first
    $permissionGroups = [
      'user' => [
        'view_any' => 'Access user listing',
        'view' => 'View user details',
        'create' => 'Create new users',
        'update' => 'Update user details',
        'delete' => 'Delete users',
        'restore' => 'Restore deleted users',
        'force_delete' => 'Permanently delete users',
        'impersonate' => 'Impersonate other users',
      ],
      'billboard' => [
        'view_any' => 'Access billboard listing',
        'view' => 'View billboard details',
        'create' => 'Create new billboards',
        'update' => 'Update billboard details',
        'delete' => 'Delete billboards',
        'restore' => 'Restore deleted billboards',
        'force_delete' => 'Permanently delete billboards',
      ],
      'location' => [
        'view_any' => 'Access location listing',
        'view' => 'View location details',
        'create' => 'Create new locations',
        'update' => 'Update location details',
        'delete' => 'Delete locations',
        'restore' => 'Restore deleted locations',
        'force_delete' => 'Permanently delete locations',
      ],
      'contract' => [
        'view_any' => 'Access contract listing',
        'view' => 'View contract details',
        'create' => 'Create new contracts',
        'update' => 'Update contract details',
        'delete' => 'Delete contracts',
        'restore' => 'Restore deleted contracts',
        'force_delete' => 'Permanently delete contracts',
        'approve' => 'Approve contracts',
        'reject' => 'Reject contracts',
      ],
      'quote' => [
        'view_any' => 'Access quote listing',
        'view' => 'View quote details',
        'create' => 'Create new quotes',
        'update' => 'Update quotes',
        'delete' => 'Delete quotes',
        'approve' => 'Approve quotes',
        'reject' => 'Reject quotes',
      ],
    ];

    // Create permissions
    $allPermissions = [];
    foreach ($permissionGroups as $group => $permissions) {
      foreach ($permissions as $action => $description) {
        $permissionName = "{$action}_{$group}";
        if ($action === 'view_any' || $action === 'view') {
          $permissionName = "{$action}_{$group}";
        }
        $permission = Permission::create([
          'name' => $permissionName,
          'description' => $description,
        ]);
        $allPermissions[] = $permission->name;
      }
    }

    // Add special permissions
    Permission::create([
      'name' => 'manage_notification_settings',
      'description' => 'Manage notification preferences'
    ]);

    // Create roles and assign permissions
    foreach ($roles as $roleName => $roleData) {
      $role = Role::create([
        'name' => $roleName,
        'description' => $roleData['description'],
      ]);

      if ($roleName === 'super_admin') {
        $role->givePermissionTo(Permission::all());
      } else {
        $role->givePermissionTo($roleData['permissions']);
      }
    }

    // Create users with their respective roles
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
      'viewer' => [
        'name' => 'Viewer User',
        'email' => 'viewer@example.com',
      ],
    ];

    foreach ($users as $role => $userData) {
      $user = User::create([
        'name' => $userData['name'],
        'email' => $userData['email'],
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
        'is_active' => true,
        'created_at' => '2025-04-27 19:44:18',
      ]);

      $user->assignRole($role);
      $this->command->info("Created {$role}: {$user->email}");
    }

    $this->command->info('All roles, permissions, and users created successfully!');
  }
}
