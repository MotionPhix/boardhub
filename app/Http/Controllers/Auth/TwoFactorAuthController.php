<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class TwoFactorAuthController extends Controller
{
    protected TwoFactorAuthenticationProvider $twoFactorProvider;

    public function __construct(TwoFactorAuthenticationProvider $twoFactorProvider)
    {
        $this->twoFactorProvider = $twoFactorProvider;
    }

    public function setup(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Auth/TwoFactor/Setup', [
            'user' => $user->only(['id', 'name', 'email']),
            'two_factor_enabled' => $user->two_factor_enabled ?? false,
            'recovery_codes_generated' => $user->two_factor_recovery_codes ? true : false,
        ]);
    }

    public function enable(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password',
            ], 401);
        }

        if ($user->two_factor_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication is already enabled',
            ], 400);
        }

        // Generate secret key
        $secret = $this->twoFactorProvider->generateSecretKey();

        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => null,
        ])->save();

        // Generate QR code data
        $qrCodeUrl = $this->twoFactorProvider->qrCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        return response()->json([
            'success' => true,
            'secret_key' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'manual_entry_key' => $secret,
        ]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if ($user->two_factor_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication is already enabled',
            ], 400);
        }

        if (!$user->two_factor_secret) {
            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication setup not started',
            ], 400);
        }

        $secret = decrypt($user->two_factor_secret);

        if (!$this->twoFactorProvider->verify($request->code, $secret)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code',
            ], 400);
        }

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ])->save();

        // Log activity
        activity()
            ->performedOn($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('Two-factor authentication enabled');

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication enabled successfully',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password',
            ], 401);
        }

        if (!$user->two_factor_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication is not enabled',
            ], 400);
        }

        $user->forceFill([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        // Log activity
        activity()
            ->performedOn($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('Two-factor authentication disabled');

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication disabled successfully',
        ]);
    }

    public function recoveryCodes(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->two_factor_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication is not enabled',
            ], 400);
        }

        if (!$user->two_factor_recovery_codes) {
            return response()->json([
                'success' => false,
                'message' => 'No recovery codes available',
            ], 400);
        }

        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        return response()->json([
            'success' => true,
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password',
            ], 401);
        }

        if (!$user->two_factor_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication is not enabled',
            ], 400);
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ])->save();

        // Log activity
        activity()
            ->performedOn($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('Two-factor recovery codes regenerated');

        return response()->json([
            'success' => true,
            'message' => 'Recovery codes regenerated successfully',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    private function generateRecoveryCodes(): array
    {
        return Collection::times(8, function () {
            return Str::random(10);
        })->all();
    }
}