<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Membership;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Services\TenantSessionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function __construct(
        private TenantSessionService $tenantSessionService
    ) {}

    /**
     * Display organization selection/switching interface
     */
    public function index(): Response
    {
        $user = Auth::user();
        $organizations = $this->getAccessibleOrganizations($user);
        $currentOrganization = $this->getCurrentOrganization();
        $analytics = $this->getOrganizationAnalytics($user);

        return Inertia::render('tenant/Index', [
            'tenants' => $organizations, // Backend uses tenant, frontend sees as organizations
            'currentTenant' => $currentOrganization,
            'analytics' => $analytics,
            'breadcrumbs' => $this->tenantSessionService->getTenantBreadcrumbs(),
        ]);
    }

    /**
     * Get real analytics data for organization switching
     */
    private function getOrganizationAnalytics(User $user): array
    {
        $currentTenant = $this->getCurrentOrganization();
        $accessibleTenants = collect($this->getAccessibleOrganizations($user));

        // If no current tenant from session, try to get the most recently accessed one
        if (!$currentTenant && $accessibleTenants->isNotEmpty()) {
            // Get the most recently accessed organization
            $mostRecentMembership = $user->memberships()
                ->whereIn('tenant_id', $accessibleTenants->pluck('id'))
                ->orderBy('last_accessed_at', 'desc')
                ->first();

            if ($mostRecentMembership) {
                $currentTenant = $accessibleTenants->firstWhere('id', $mostRecentMembership->tenant_id);

                // Update session with this tenant
                if ($currentTenant) {
                    session(['current_tenant_id' => $currentTenant['id']]);
                }
            }
        }

        // Get membership statistics
        $membershipStats = $user->memberships()
            ->selectRaw('role, count(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();

        // Get tenant IDs for subscription stats
        $tenantIds = $accessibleTenants->pluck('id')->filter()->toArray();

        // Get subscription stats for user's organizations
        $subscriptionStats = [];
        if (!empty($tenantIds)) {
            $subscriptionStats = TenantSubscription::whereIn('tenant_id', $tenantIds)
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
        }

        // Get REAL recent activities - actual membership updates, tenant switches, etc.
        $recentActivities = $user->memberships()
            ->where('updated_at', '>=', now()->subDays(7))
            ->with('tenant')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($membership) {
                return [
                    'type' => 'membership_updated',
                    'description' => "Role updated in {$membership->tenant->name}",
                    'organization' => $membership->tenant->name,
                    'date' => $membership->updated_at->diffForHumans(),
                ];
            });

        // Add tenant creation activities if user created any recently
        $createdTenants = Tenant::whereHas('memberships', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->where('role', Membership::ROLE_OWNER);
            })
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($tenant) {
                return [
                    'type' => 'organization_created',
                    'description' => "Created organization: {$tenant->name}",
                    'organization' => $tenant->name,
                    'date' => $tenant->created_at->diffForHumans(),
                ];
            });

        $allActivities = $recentActivities->concat($createdTenants)
            ->sortByDesc('date')
            ->take(5)
            ->values();

        // Calculate trial organizations count
        $trialCount = $accessibleTenants->filter(function ($tenant) {
            return isset($tenant['trial_ends_at']) &&
                   $tenant['trial_ends_at'] &&
                   \Carbon\Carbon::parse($tenant['trial_ends_at'])->isFuture();
        })->count();

        // Get organizations by status for the chart - load actual models to get status
        $tenantIds = $accessibleTenants->pluck('id')->filter()->toArray();
        $actualTenants = [];
        if (!empty($tenantIds)) {
            $actualTenants = \App\Models\Tenant::whereIn('id', $tenantIds)->get();
        }

        $organizationsByStatus = collect($actualTenants)->countBy(function ($tenant) {
            return $tenant->status->value;
        });

        return [
            'total_organizations' => $accessibleTenants->count(),
            'current_organization_id' => $currentTenant['id'] ?? null,
            'current_organization_name' => $currentTenant['name'] ?? null,
            'membership_roles' => $membershipStats,
            'subscription_statuses' => $subscriptionStats,
            'recent_activities' => $allActivities->toArray(),
            'trial_organizations' => $trialCount,
            'organizations_by_status' => $organizationsByStatus->toArray(),
        ];
    }

    /**
     * Get accessible organizations for user (mapped from tenants)
     */
    private function getAccessibleOrganizations(User $user)
    {
        return $this->tenantSessionService->getAccessibleTenants($user);
    }

    /**
     * Get current organization (mapped from tenant)
     */
    private function getCurrentOrganization()
    {
        return $this->tenantSessionService->getCurrentTenant();
    }

    /**
     * Show organization creation form
     */
    public function create(): Response
    {
        $billingPlans = \App\Models\BillingPlan::active()
            ->ordered()
            ->get();

        return Inertia::render('tenant/Create', [
            'billingPlans' => $billingPlans,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    // Check if organization name is unique for this user
                    $existingTenant = $request->user()->tenants()
                        ->where('name', $value)
                        ->exists();

                    if ($existingTenant) {
                        $fail('You already have an organization with this name.');
                    }
                }
            ],
            'description' => 'nullable|string|max:1000',
            'slug' => 'required|string|max:255|unique:tenants,slug',
            'subdomain' => 'nullable|string|max:255|unique:tenants,subdomain',
            'plan' => 'required|string|exists:billing_plans,name',
            'settings' => 'array',
            'settings.primary_color' => 'nullable|string',
            'settings.secondary_color' => 'nullable|string',
            'settings.theme' => 'nullable|string',
            'settings.features' => 'array',
        ]);

        // Get the billing plan
        $billingPlan = \App\Models\BillingPlan::where('name', $validated['plan'])->first();

        // For trial plans, create organization directly
        if ($billingPlan->name === 'trial' || $billingPlan->price == 0) {
            return $this->createOrganizationWithTrialSubscription($request, $validated, $billingPlan);
        }

        // For paid plans, redirect to checkout
        return $this->redirectToCheckout($request, $validated, $billingPlan);
    }

    private function createOrganizationWithTrialSubscription($request, $validated, $billingPlan)
    {
        // Create the tenant/organization
        $tenant = Tenant::create([
            'id' => Str::uuid(),
            'name' => $validated['name'],
            'description' => $validated['description'],
            'slug' => $validated['slug'],
            'subdomain' => $validated['subdomain'],
            'plan' => $validated['plan'],
            'settings' => $validated['settings'] ?? [],
            'status' => 'active',
            'trial_ends_at' => $billingPlan->trial_days > 0 ? now()->addDays($billingPlan->trial_days) : null,
        ]);

        // Create the trial subscription
        \App\Models\TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'billing_plan_id' => $billingPlan->id,
            'status' => 'trial',
            'payment_status' => null,
            'amount' => $billingPlan->price,
            'currency' => 'ZMW',
            'interval' => 'monthly',
            'trial_ends_at' => $tenant->trial_ends_at,
            'current_period_start' => now(),
            'current_period_end' => $tenant->trial_ends_at,
        ]);

        // Add the current user as the owner of this organization
        $request->user()->joinTenant($tenant->id, Membership::ROLE_OWNER);

        return redirect()->route('organizations.index')->with('success', 'Organization created successfully! Your trial period has started.');
    }

    private function redirectToCheckout($request, $validated, $billingPlan)
    {
        // Store organization data in session for checkout completion
        session()->put('pending_organization', [
            'name' => $validated['name'],
            'description' => $validated['description'],
            'slug' => $validated['slug'],
            'subdomain' => $validated['subdomain'],
            'plan' => $validated['plan'],
            'settings' => $validated['settings'] ?? [],
            'billing_plan_id' => $billingPlan->id,
        ]);

        // Redirect to checkout page
        return redirect()->route('checkout.index', ['plan' => $billingPlan->name])
            ->with('info', 'Complete your payment to create your organization.');
    }

    /**
     * Switch to a specific organization
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

        // Validate user can access this organization
        if (!$this->tenantSessionService->canSwitchToTenant($user, $tenantId)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this organization.',
            ], 403);
        }

        // Perform the switch
        $success = $this->tenantSessionService->switchToTenant($user, $tenantId);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to switch organization.',
            ], 500);
        }

        // Update the last accessed time for this membership
        $membership = $user->getMembershipFor($tenantId);
        if ($membership) {
            $membership->update(['last_accessed_at' => now()]);
        }

        $tenant = Tenant::find($tenantId);
        $redirectUrl = $request->input('redirect_url') ?: route('tenant.dashboard', ['tenant' => $tenant->uuid]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Switched to {$tenant->name}",
                'organization' => [
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
     * Get accessible organizations for current user (API)
     */
    public function getAccessibleOrganizationsApi(): JsonResponse
    {
        $user = Auth::user();
        $accessibleOrganizations = $this->getAccessibleOrganizations($user);
        $currentOrganization = $this->getCurrentOrganization();

        return response()->json([
            'success' => true,
            'data' => [
                'accessible_organizations' => $accessibleOrganizations,
                'current_organization' => $currentOrganization,
                'analytics' => $this->getOrganizationAnalytics($user),
            ],
        ]);
    }

    /**
     * Logout from specific organization on this device
     */
    public function logoutFromOrganization(Request $request): JsonResponse
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
            'message' => 'Logged out from organization on this device.',
        ]);
    }

    /**
     * Accept organization invitation
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

            return redirect()->route('organizations.index')
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

            return redirect()->route('organizations.index')
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

            return redirect()->route('organizations.index')
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

            return redirect()->route('organizations.index')
                ->with('error', 'Failed to accept invitation.');
        }

        // Switch to the new organization
        $this->tenantSessionService->switchToTenant(Auth::user(), $membership->tenant_id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Welcome to {$membership->tenant->name}!",
                'organization' => [
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
     * Refresh accessible organizations (when memberships change)
     */
    public function refreshAccessibleOrganizations(): JsonResponse
    {
        $user = Auth::user();
        $this->tenantSessionService->refreshAccessibleTenants($user);

        return response()->json([
            'success' => true,
            'message' => 'Accessible organizations refreshed.',
            'data' => [
                'accessible_organizations' => $this->getAccessibleOrganizations($user),
                'current_organization' => $this->getCurrentOrganization(),
            ],
        ]);
    }

    /**
     * Get organization context for current session
     */
    public function getOrganizationContext(): JsonResponse
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
                'current_organization' => $this->getCurrentOrganization(),
                'accessible_organizations' => $this->getAccessibleOrganizations($user),
            ],
        ]);
    }

    /**
     * Get organization switching breadcrumbs for navigation
     */
    public function getBreadcrumbs(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->tenantSessionService->getTenantBreadcrumbs(),
        ]);
    }
}