<?php

namespace App\Actions\Fortify;

use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     */
    protected function passwordRules(): array
    {
        return [
            'required',
            'string',
            Password::min(12)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(3), // Check against known data breaches
            'confirmed',
        ];
    }
}