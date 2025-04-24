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

    // Create permissions for users
    $userPermissions = [
      'view_any_user' => 'Access user listing',
      'view_user' => 'View user details',
      'create_user' => 'Create new users',
      'update_user' => 'Update user details',
      'delete_user' => 'Delete users',
      'restore_user' => 'Restore deleted users',
      'force_delete_user' => 'Permanently delete users',
      'impersonate_users' => 'Impersonate other users',
    ];

    // Create permissions for roles
    $rolePermissions = [
      'view_any_role' => 'Access role listing',
      'view_role' => 'View role details',
      'create_role' => 'Create new roles',
      'update_role' => 'Update role details',
      'delete_role' => 'Delete roles',
    ];

    // Create permissions for billboards
    $billboardPermissions = [
      'view_any_billboard' => 'Access billboard listing',
      'view_billboard' => 'View billboard details',
      'create_billboard' => 'Create new billboards',
      'update_billboard' => 'Update billboard details',
      'delete_billboard' => 'Delete billboards',
      'restore_billboard' => 'Restore deleted billboards',
      'force_delete_billboard' => 'Permanently delete billboards',
    ];

    // Create permissions for locations
    $locationPermissions = [
      'view_any_location' => 'Access location listing',
      'view_location' => 'View location details',
      'create_location' => 'Create new locations',
      'update_location' => 'Update location details',
      'delete_location' => 'Delete locations',
      'restore_location' => 'Restore deleted locations',
      'force_delete_location' => 'Permanently delete locations',
    ];

    // Create permissions for contracts
    $contractPermissions = [
      'view_any_contract' => 'Access contract listing',
      'view_contract' => 'View contract details',
      'create_contract' => 'Create new contracts',
      'update_contract' => 'Update contract details',
      'delete_contract' => 'Delete contracts',
      'restore_contract' => 'Restore deleted contracts',
      'force_delete_contract' => 'Permanently delete contracts',
      'approve_contract' => 'Approve contracts',
      'reject_contract' => 'Reject contracts',
    ];

    // Create permissions
    $allPermissions = array_merge(
      $userPermissions,
      $rolePermissions,
      $billboardPermissions,
      $locationPermissions,
      $contractPermissions
    );

    foreach ($allPermissions as $permission => $description) {
      Permission::create([
        'name' => $permission,
        'description' => $description,
      ]);
    }

    // Create roles and assign permissions
    $roles = [
      'super_admin' => 'Full access to all features',
      'admin' => 'Administrative access with some restrictions',
      'manager' => 'Manage billboards and contracts',
      'sales' => 'Handle sales and contracts',
      'agent' => 'View and create contracts',
      'user' => 'Basic user access',
    ];

    foreach ($roles as $role => $description) {
      $newRole = Role::create([
        'name' => $role,
        'description' => $description,
      ]);

      // Assign permissions based on role
      switch ($role) {
        case 'super_admin':
          $newRole->givePermissionTo(Permission::all());
          break;

        case 'admin':
          $newRole->givePermissionTo(array_merge(
            array_keys($userPermissions),
            array_keys($billboardPermissions),
            array_keys($locationPermissions),
            array_keys($contractPermissions)
          ));
          break;

        case 'manager':
          $newRole->givePermissionTo([
            'view_any_user',
            'view_user',
            ...array_keys($billboardPermissions),
            ...array_keys($locationPermissions),
            ...array_keys($contractPermissions),
          ]);
          break;

        case 'sales':
          $newRole->givePermissionTo([
            'view_any_billboard',
            'view_billboard',
            'view_any_location',
            'view_location',
            'view_any_contract',
            'view_contract',
            'create_contract',
            'update_contract',
          ]);
          break;

        case 'agent':
          $newRole->givePermissionTo([
            'view_any_billboard',
            'view_billboard',
            'view_any_location',
            'view_location',
            'view_any_contract',
            'view_contract',
            'create_contract',
          ]);
          break;

        case 'user':
          $newRole->givePermissionTo([
            'view_any_billboard',
            'view_billboard',
            'view_any_location',
            'view_location',
          ]);
          break;
      }
    }

    // Create default super admin user
    $user = User::create([
      'name' => 'MotionPhix',
      'email' => 'boss@example.com',
      'password' => Hash::make('password'),
      'email_verified_at' => now(),
      'is_admin' => true,
      'is_active' => true,
      'created_at' => '2025-04-24 14:37:14',
    ]);

    $user->assignRole('super_admin');
  }
}
