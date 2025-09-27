<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\Tenant;
use App\Services\TenantSessionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class TenantSwitchController extends Controller
{
    public function __construct(
        private TenantSessionService $tenantSessionService
    ) {}

    /**
     * Display tenant selection/switching interface
     */
    public function index(): Response
    {
        $user = Auth::user();
        $accessibleTenants = $this->tenantSessionService->getAccessibleTenants($user);
        $currentTenant = $this->tenantSessionService->getCurrentTenant();
        $analytics = $this->tenantSessionService->getTenantSwitchingAnalytics($user);

        return Inertia::render('tenant-switch/Index', [
            'accessible_tenants' => $accessibleTenants,
            'current_tenant' => $currentTenant,
            'analytics' => $analytics,
            'breadcrumbs' => $this->tenantSessionService->getTenantBreadcrumbs(),
        ]);
    }

    /**
     * Switch to a specific tenant
     */
    public function switch(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'redirect_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $tenantId = $request->input('tenant_id');

        // Validate user can access this tenant
        if (!$this->tenantSessionService->canSwitchToTenant($user, $tenantId)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this tenant.',
            ], 403);
        }

        // Perform the switch
        $success = $this->tenantSessionService->switchToTenant($user, $tenantId);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to switch tenant.',
            ], 500);
        }

        $tenant = Tenant::find($tenantId);
        $redirectUrl = $request->input('redirect_url') ?: route('tenant.dashboard', ['tenant' => $tenant->uuid]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Switched to {$tenant->name}",
                'tenant' => [
                    'id' => $tenant->id,
                    'uuid' => $tenant->uuid,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                ],
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect($redirectUrl)
            ->with('success', "Switched to {$tenant->name}");
    }

    /**
     * Get accessible tenants for current user (API)
     */
    public function getAccessibleTenants(): JsonResponse
    {
        $user = Auth::user();
        $accessibleTenants = $this->tenantSessionService->getAccessibleTenants($user);
        $currentTenant = $this->tenantSessionService->getCurrentTenant();

        return response()->json([
            'success' => true,
            'data' => [
                'accessible_tenants' => $accessibleTenants,
                'current_tenant' => $currentTenant,
                'analytics' => $this->tenantSessionService->getTenantSwitchingAnalytics($user),
            ],
        ]);
    }

    /**
     * Logout from specific tenant on this device
     */
    public function logoutFromTenant(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer|exists:tenants,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $tenantId = $request->input('tenant_id');
        $this->tenantSessionService->logoutFromTenant($tenantId);

        return response()->json([
            'success' => true,
            'message' => 'Logged out from tenant on this device.',
        ]);
    }

    /**
     * Accept tenant invitation
     */
    public function acceptInvitation(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|size:64',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            return redirect()->route('tenant.select')
                ->with('error', 'Invalid invitation token.');
        }

        $token = $request->input('token');

        // Find the membership invitation
        $membership = Membership::where('invitation_token', $token)
            ->where('status', Membership::STATUS_PENDING)
            ->with('tenant')
            ->first();

        if (!$membership) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired invitation.',
                ], 404);
            }

            return redirect()->route('tenant.select')
                ->with('error', 'Invalid or expired invitation.');
        }

        // Ensure the invitation is for the authenticated user
        if ($membership->user_id !== Auth::id()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This invitation is not for your account.',
                ], 403);
            }

            return redirect()->route('tenant.select')
                ->with('error', 'This invitation is not for your account.');
        }

        // Accept the invitation
        $success = $this->tenantSessionService->acceptInvitation($token);

        if (!$success) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to accept invitation.',
                ], 500);
            }

            return redirect()->route('tenant.select')
                ->with('error', 'Failed to accept invitation.');
        }

        // Switch to the new tenant
        $this->tenantSessionService->switchToTenant(Auth::user(), $membership->tenant_id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Welcome to {$membership->tenant->name}!",
                'tenant' => [
                    'id' => $membership->tenant->id,
                    'uuid' => $membership->tenant->uuid,
                    'name' => $membership->tenant->name,
                ],
                'redirect_url' => route('tenant.dashboard', ['tenant' => $membership->tenant->uuid]),
            ]);
        }

        return redirect()->route('tenant.dashboard', ['tenant' => $membership->tenant->uuid])
            ->with('success', "Welcome to {$membership->tenant->name}!");
    }

    /**
     * Refresh accessible tenants (when memberships change)
     */
    public function refreshAccessibleTenants(): JsonResponse
    {
        $user = Auth::user();
        $this->tenantSessionService->refreshAccessibleTenants($user);

        return response()->json([
            'success' => true,
            'message' => 'Accessible tenants refreshed.',
            'data' => [
                'accessible_tenants' => $this->tenantSessionService->getAccessibleTenants($user),
                'current_tenant' => $this->tenantSessionService->getCurrentTenant(),
            ],
        ]);
    }

    /**
     * Get tenant context for current session
     */
    public function getTenantContext(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->tenantSessionService->getTenantContext(),
        ]);
    }

    /**
     * Validate session integrity
     */
    public function validateSession(): JsonResponse
    {
        $user = Auth::user();
        $isValid = $this->tenantSessionService->validateSessionIntegrity($user);

        if (!$isValid) {
            // Clear invalid session
            $this->tenantSessionService->clearAllTenantSessions();
            $this->tenantSessionService->initializeUserSession($user);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'is_valid' => $isValid,
                'current_tenant' => $this->tenantSessionService->getCurrentTenant(),
                'accessible_tenants' => $this->tenantSessionService->getAccessibleTenants($user),
            ],
        ]);
    }

    /**
     * Get tenant switching breadcrumbs for navigation
     */
    public function getBreadcrumbs(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->tenantSessionService->getTenantBreadcrumbs(),
        ]);
    }
}