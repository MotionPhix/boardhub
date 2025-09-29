<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Events\TenantOnboardingStarted;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    /**
     * Show the onboarding progress dashboard
     */
    public function index(): Response
    {
        $tenant = $this->getCurrentTenant();
        $progress = $this->getOnboardingProgress($tenant);

        return Inertia::render('tenant/onboarding/Index', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'setup_completed' => $tenant->setup_completed,
                'onboarding_progress' => $tenant->onboarding_progress ?? [],
            ],
            'progress' => $progress,
            'current_step' => $this->getCurrentStep($progress),
            'next_steps' => $this->getNextSteps($progress),
        ]);
    }

    /**
     * Show business information setup step
     */
    public function businessInfo(): Response
    {
        $tenant = $this->getCurrentTenant();

        return Inertia::render('tenant/onboarding/BusinessInfo', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'business_type' => $tenant->business_type,
                'industry' => $tenant->industry,
                'company_size' => $tenant->company_size,
                'contact_info' => $tenant->contact_info ?? [],
            ],
            'business_types' => $this->getBusinessTypes(),
            'industries' => $this->getIndustries(),
            'company_sizes' => $this->getCompanySizes(),
        ]);
    }

    /**
     * Update business information
     */
    public function updateBusinessInfo(Request $request): RedirectResponse|JsonResponse
    {
        $tenant = $this->getCurrentTenant();

        $validated = $request->validate([
            'business_type' => 'required|string|max:255',
            'industry' => 'required|string|max:255',
            'company_size' => 'required|string|max:255',
            'contact_info' => 'required|array',
            'contact_info.phone' => 'nullable|string|max:20',
            'contact_info.address' => 'nullable|string|max:500',
            'contact_info.city' => 'nullable|string|max:100',
            'contact_info.country' => 'nullable|string|max:100',
            'contact_info.website' => 'nullable|url|max:255',
        ]);

        $tenant->update($validated);

        // Fire onboarding event
        TenantOnboardingStarted::fire(
            tenant_id: $tenant->id,
            onboarding_step: 'business_info'
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Business information updated successfully!',
                'setup_completed' => $tenant->fresh()->setup_completed,
            ]);
        }

        return redirect()->route('tenant.onboarding.team-setup')
            ->with('success', 'Business information saved! Let\'s set up your team.');
    }

    /**
     * Show team setup step
     */
    public function teamSetup(): Response
    {
        $tenant = $this->getCurrentTenant();

        return Inertia::render('tenant/onboarding/TeamSetup', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ],
            'members' => $tenant->activeMembers()->with('user')->get()->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->user->name,
                    'email' => $member->user->email,
                    'role' => $member->role,
                    'status' => $member->status,
                    'joined_at' => $member->joined_at,
                ];
            }),
            'pending_invitations' => $tenant->memberships()
                ->where('status', 'pending')
                ->with('user')
                ->get()
                ->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'email' => $member->user->email,
                        'role' => $member->role,
                        'invited_at' => $member->created_at,
                    ];
                }),
        ]);
    }

    /**
     * Show branding setup step
     */
    public function branding(): Response
    {
        $tenant = $this->getCurrentTenant();

        return Inertia::render('tenant/onboarding/Branding', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'logo_url' => $tenant->logo_url,
                'primary_color' => $tenant->primary_color,
                'secondary_color' => $tenant->secondary_color,
                'branding_settings' => $tenant->branding_settings ?? [],
            ],
        ]);
    }

    /**
     * Update branding information
     */
    public function updateBranding(Request $request): RedirectResponse|JsonResponse
    {
        $tenant = $this->getCurrentTenant();

        $validated = $request->validate([
            'logo_url' => 'nullable|url|max:255',
            'primary_color' => 'required|string|size:7|regex:/^#[0-9A-F]{6}$/i',
            'secondary_color' => 'required|string|size:7|regex:/^#[0-9A-F]{6}$/i',
            'branding_settings' => 'nullable|array',
        ]);

        $tenant->update($validated);

        // Fire onboarding event
        TenantOnboardingStarted::fire(
            tenant_id: $tenant->id,
            onboarding_step: 'branding'
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Branding updated successfully!',
            ]);
        }

        return redirect()->route('tenant.onboarding.complete')
            ->with('success', 'Branding saved! Your onboarding is almost complete.');
    }

    /**
     * Show onboarding completion page
     */
    public function complete(): Response
    {
        $tenant = $this->getCurrentTenant();
        $progress = $this->getOnboardingProgress($tenant);

        return Inertia::render('tenant/onboarding/Complete', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'setup_completed' => $tenant->setup_completed,
            ],
            'progress' => $progress,
            'completion_percentage' => $this->calculateCompletionPercentage($progress),
        ]);
    }

    /**
     * Skip onboarding step
     */
    public function skip(Request $request): RedirectResponse|JsonResponse
    {
        $step = $request->input('step');
        $tenant = $this->getCurrentTenant();

        // Fire onboarding event for skipped step
        TenantOnboardingStarted::fire(
            tenant_id: $tenant->id,
            onboarding_step: $step
        );

        $nextRoute = match($step) {
            'business_info' => 'tenant.onboarding.team-setup',
            'team_setup' => 'tenant.onboarding.branding',
            'branding' => 'tenant.onboarding.complete',
            default => 'tenant.dashboard',
        };

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Step skipped successfully!',
                'next_route' => route($nextRoute),
            ]);
        }

        return redirect()->route($nextRoute)
            ->with('info', 'Step skipped. You can complete it later from your settings.');
    }

    // Helper methods

    private function getCurrentTenant(): Tenant
    {
        $tenantId = session('current_tenant_id');
        return Tenant::findOrFail($tenantId);
    }

    private function getOnboardingProgress(Tenant $tenant): array
    {
        $criteria = $tenant->getSetupCompletionCriteria();

        return [
            'business_info' => $criteria['has_business_info'],
            'team_setup' => $criteria['has_owner'], // At least owner exists
            'branding' => !empty($tenant->logo_url) || $tenant->primary_color !== '#6366f1',
            'setup_completed' => $tenant->setup_completed,
        ];
    }

    private function getCurrentStep(array $progress): string
    {
        if (!$progress['business_info']) return 'business_info';
        if (!$progress['team_setup']) return 'team_setup';
        if (!$progress['branding']) return 'branding';
        return 'complete';
    }

    private function getNextSteps(array $progress): array
    {
        $steps = [];

        if (!$progress['business_info']) {
            $steps[] = [
                'name' => 'Business Information',
                'description' => 'Tell us about your business',
                'route' => 'tenant.onboarding.business-info',
                'icon' => 'building-2',
            ];
        }

        if (!$progress['team_setup']) {
            $steps[] = [
                'name' => 'Team Setup',
                'description' => 'Invite team members',
                'route' => 'tenant.onboarding.team-setup',
                'icon' => 'users',
            ];
        }

        if (!$progress['branding']) {
            $steps[] = [
                'name' => 'Branding',
                'description' => 'Customize your organization',
                'route' => 'tenant.onboarding.branding',
                'icon' => 'palette',
            ];
        }

        return $steps;
    }

    private function calculateCompletionPercentage(array $progress): int
    {
        $completed = count(array_filter($progress));
        $total = count($progress);
        return round(($completed / $total) * 100);
    }

    private function getBusinessTypes(): array
    {
        return [
            'advertising_agency' => 'Advertising Agency',
            'marketing_company' => 'Marketing Company',
            'real_estate' => 'Real Estate',
            'retail' => 'Retail',
            'hospitality' => 'Hospitality',
            'automotive' => 'Automotive',
            'healthcare' => 'Healthcare',
            'education' => 'Education',
            'technology' => 'Technology',
            'other' => 'Other',
        ];
    }

    private function getIndustries(): array
    {
        return [
            'advertising_marketing' => 'Advertising & Marketing',
            'automotive' => 'Automotive',
            'construction' => 'Construction',
            'education' => 'Education',
            'entertainment' => 'Entertainment',
            'fashion' => 'Fashion',
            'finance' => 'Finance',
            'food_beverage' => 'Food & Beverage',
            'healthcare' => 'Healthcare',
            'hospitality' => 'Hospitality',
            'real_estate' => 'Real Estate',
            'retail' => 'Retail',
            'technology' => 'Technology',
            'transportation' => 'Transportation',
            'other' => 'Other',
        ];
    }

    private function getCompanySizes(): array
    {
        return [
            'solo' => 'Just me',
            'small' => '2-10 employees',
            'medium' => '11-50 employees',
            'large' => '51-200 employees',
            'enterprise' => '200+ employees',
        ];
    }
}
