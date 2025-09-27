<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;
use Spatie\Activitylog\Facades\Activity;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and update the user's password.
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $this->passwordRules(),
        ], [
            'current_password.current_password' => __('The provided password does not match your current password.'),
        ])->validate();

        $user->forceFill([
            'password' => Hash::make($input['password']),
            'password_changed_at' => now(),
        ])->save();

        // Log password change
        Activity::log('Password changed')
            ->causedBy($user)
            ->withProperties([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
    }
}