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

    $roles = [
      'super_admin' => [
        'description' => 'Full access to all features',
        // super_admin gets all permissions automatically
      ],
      'admin' => [
        'description' => 'Administrative access with some restrictions',
        'permissions' => [
          'view_billboard',
          'view_any_billboard',
          'create_billboard',
          'update_billboard',
          'delete_billboard',
          'restore_billboard',
          'force_delete_billboard',

          'view_location',
          'view_any_location',
          'create_location',
          'update_location',
          'delete_location',
          'restore_location',
          'force_delete_location',

          'view_contract',
          'view_any_contract',
          'create_contract',
          'update_contract',
          'delete_contract',
          'restore_contract',
          'force_delete_contract',
          'approve_contract',
          'reject_contract',

          'view_quote',
          'view_any_quote',
          'create_quote',
          'update_quote',
          'delete_quote',
          'approve_quote',
          'reject_quote',

          'view_user',
          'view_any_user',
          'create_user',
          'update_user',
        ]
      ],
      'manager' => [
        'description' => 'Manage billboards and contracts',
        'permissions' => [
          'view_billboard',
          'view_any_billboard',
          'create_billboard',
          'update_billboard',

          'view_location',
          'view_any_location',
          'create_location',
          'update_location',

          'view_contract',
          'view_any_contract',
          'create_contract',
          'update_contract',
          'approve_contract',
          'reject_contract',

          'view_quote',
          'view_any_quote',
          'create_quote',
          'update_quote',
          'approve_quote',
          'reject_quote',

          'view_user',
          'view_any_user',
        ]
      ],
      'agent' => [
        'description' => 'Handle sales and contracts',
        'permissions' => [
          'view_billboard',
          'view_any_billboard',
          'view_location',
          'view_any_location',
          'view_contract',
          'view_any_contract',
          'create_contract',
          'view_quote',
          'view_any_quote',
          'create_quote',
          'update_quote',
        ]
      ],
      'viewer' => [
        'description' => 'View-only access',
        'permissions' => [
          'view_billboard',
          'view_any_billboard',
          'view_location',
          'view_any_location',
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
        'created_at' => '2025-05-07 18:38:06',
      ]);

      $user->assignRole($role);
    }
  }
}
