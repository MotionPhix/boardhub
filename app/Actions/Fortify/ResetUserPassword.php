<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Spatie\Activitylog\Facades\Activity;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password' => Hash::make($input['password']),
            'password_changed_at' => now(),
        ])->save();

        // Log password reset
        Activity::log('Password reset via email')
            ->causedBy($user)
            ->withProperties([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
    }
}