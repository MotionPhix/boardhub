<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
  use HandlesAuthorization;

  public function viewAny(User $user): bool
  {
    return $user->can('view_any_user');
  }

  public function view(User $user, User $model): bool
  {
    return $user->can('view_user') || $user->id === $model->id;
  }

  public function create(User $user): bool
  {
    return $user->can('create_user');
  }

  public function update(User $user, User $model): bool
  {
    return $user->can('update_user') || $user->id === $model->id;
  }

  public function delete(User $user, User $model): bool
  {
    if ($model->hasRole('super_admin')) {
      return false;
    }

    return $user->can('delete_user') && $user->id !== $model->id;
  }

  public function restore(User $user, User $model): bool
  {
    return $user->can('restore_user');
  }

  public function forceDelete(User $user, User $model): bool
  {
    if ($model->hasRole('super_admin')) {
      return false;
    }

    return $user->can('force_delete_user') && $user->id !== $model->id;
  }

  public function impersonate(User $user, User $model): bool
  {
    if ($model->hasRole('super_admin') || $user->id === $model->id) {
      return false;
    }

    return $user->can('impersonate_users');
  }
}
