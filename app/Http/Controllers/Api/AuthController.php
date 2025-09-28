<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'device_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account is not active',
            ], 403);
        }

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'device_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user' => $request->user()->load(['roles', 'permissions']),
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roles', 'permissions']);

        return response()->json([
            'success' => true,
            'user' => $user,
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $request->user()->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'user' => $user->fresh(),
            'message' => 'Profile updated successfully',
        ]);
    }

    public function permissions(Request $request): JsonResponse
    {
        $user = $request->user();
        $permissions = $user->getAllPermissions()->pluck('name');
        $roles = $user->getRoleNames();

        return response()->json([
            'success' => true,
            'permissions' => $permissions,
            'roles' => $roles,
        ]);
    }

    public function revokeTokens(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token_ids' => 'array',
            'token_ids.*' => 'integer|exists:personal_access_tokens,id',
            'revoke_all' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if ($request->boolean('revoke_all')) {
            $user->tokens()->delete();
            $message = 'All tokens revoked successfully';
        } elseif ($request->has('token_ids')) {
            $user->tokens()->whereIn('id', $request->token_ids)->delete();
            $message = 'Selected tokens revoked successfully';
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No tokens specified for revocation',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        // Create new token
        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token,
        ]);
    }
}