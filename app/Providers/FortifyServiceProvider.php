<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Controllers\Auth\PostLoginRedirectController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // Rate limiting for login attempts
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        // Rate limiting for two-factor authentication
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        // Custom Inertia views for authentication (lowercase paths)
        Fortify::loginView(function () {
            return Inertia::render('auth/Login');
        });

        Fortify::registerView(function () {
            return Inertia::render('auth/Register');
        });

        Fortify::requestPasswordResetLinkView(function () {
            return Inertia::render('auth/ForgotPassword');
        });

        Fortify::resetPasswordView(function (Request $request) {
            return Inertia::render('auth/ResetPassword', [
                'token' => $request->route('token'),
                'email' => $request->email,
            ]);
        });

        Fortify::verifyEmailView(function () {
            return Inertia::render('auth/VerifyEmail');
        });

        Fortify::twoFactorChallengeView(function () {
            return Inertia::render('auth/TwoFactorChallenge');
        });

        Fortify::confirmPasswordView(function () {
            return Inertia::render('auth/ConfirmPassword');
        });

        // Override the default redirect response after login
        Fortify::redirects('login', function (Request $request) {
            $controller = app(PostLoginRedirectController::class);
            return $controller->handlePostLoginRedirect($request->user());
        });
    }
}