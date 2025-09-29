<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->withErrors(['social' => 'Authentication failed. Please try again.']);
        }

        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            // User exists, log them in
            Auth::login($user);
        } else {
            // Create new user
            $user = User::create([
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                'email' => $socialUser->getEmail(),
                'password' => Hash::make(Str::random(24)),
                'email_verified_at' => now(),
                'is_active' => true,
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
            ]);

            event(new Registered($user));
            Auth::login($user);
        }

        // Use the post-login redirect controller to determine where to send the user
        return app(\App\Http\Controllers\Auth\PostLoginRedirectController::class)
            ->handlePostLoginRedirect($user);
    }

    private function validateProvider(string $provider): void
    {
        $allowedProviders = ['google', 'github', 'facebook', 'twitter'];

        if (!in_array($provider, $allowedProviders)) {
            abort(404, 'Provider not supported');
        }
    }
}