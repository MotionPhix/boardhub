<?php

namespace App\Http\Controllers;

use App\Events\TenantOnboardingStepCompleted;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TenantOnboardingController extends Controller
{
    public function index()
    {
        $tenant = app('tenant');

        return Inertia::render('onboarding/Index', [
            'tenant' => [
                'id' => $tenant->id,
                'uuid' => $tenant->uuid,
                'name' => $tenant->name,
                'progress' => $tenant->getOnboardingProgress(),
                'onboarding_progress' => $tenant->onboarding_progress,
                'current_step' => $this->getCurrentStep($tenant),
            ],
            'steps' => $this->getOnboardingSteps(),
        ]);
    }

    public function completeWelcome(Request $request)
    {
        $tenant = app('tenant');

        TenantOnboardingStepCompleted::fire(
            tenant_id: $tenant->id,
            step: 'welcome_completed'
        );

        return response()->json(['success' => true]);
    }

    public function completeProfile(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'business_type' => 'required|in:advertising_agency,billboard_owner,hybrid',
            'industry' => 'required|string|max:255',
            'company_size' => 'required|in:1-10,11-50,51-200,200+',
            'contact_info' => 'required|array',
            'contact_info.phone' => 'required|string',
            'contact_info.email' => 'required|email',
            'contact_info.address' => 'nullable|string',
            'contact_info.website' => 'nullable|url',
        ]);

        TenantOnboardingStepCompleted::fire(
            tenant_id: $tenant->id,
            step: 'profile_setup',
            step_data: $validated
        );

        return response()->json(['success' => true]);
    }

    public function completeBranding(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'primary_color' => 'required|regex:/^#[0-9A-F]{6}$/i',
            'secondary_color' => 'required|regex:/^#[0-9A-F]{6}$/i',
            'logo_url' => 'nullable|url',
            'branding_settings' => 'array',
        ]);

        TenantOnboardingStepCompleted::fire(
            tenant_id: $tenant->id,
            step: 'branding_configured',
            step_data: $validated
        );

        return response()->json(['success' => true]);
    }

    public function createFirstBillboard(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:500',
            'size' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'coordinates' => 'nullable|array',
            'coordinates.lat' => 'nullable|numeric',
            'coordinates.lng' => 'nullable|numeric',
        ]);

        // Create the billboard
        $billboard = $tenant->billboards()->create($validated);

        // Mark onboarding step as complete
        TenantOnboardingStepCompleted::fire(
            tenant_id: $tenant->id,
            step: 'first_billboard_added',
            step_data: ['billboard_id' => $billboard->id]
        );

        return response()->json([
            'success' => true,
            'billboard' => $billboard
        ]);
    }

    public function createFirstClient(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);

        // Create the client
        $client = $tenant->clients()->create($validated);

        // Mark onboarding step as complete
        TenantOnboardingStepCompleted::fire(
            tenant_id: $tenant->id,
            step: 'first_client_added',
            step_data: ['client_id' => $client->id]
        );

        return response()->json([
            'success' => true,
            'client' => $client
        ]);
    }

    public function inviteTeamMember(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'role' => 'required|string|in:admin,manager,agent',
        ]);

        // Check user limits
        if (!$tenant->isWithinLimits('users')) {
            return response()->json([
                'error' => 'User limit reached for your subscription tier',
                'upgrade_required' => true
            ], 422);
        }

        // Create user account
        $user = $tenant->users()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt(Str::random(16)), // Temporary password
        ]);

        // TODO: Send invitation email with password reset link

        // Mark onboarding step as complete
        TenantOnboardingStepCompleted::fire(
            tenant_id: $tenant->id,
            step: 'team_invited',
            step_data: ['user_id' => $user->id, 'role' => $validated['role']]
        );

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    public function completePaymentSetup(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'payment_method' => 'required|in:airtel_money,tnm_mpamba,bank_transfer',
            'payment_details' => 'required|array',
        ]);

        // TODO: Set up payment integration based on method

        TenantOnboardingStepCompleted::fire(
            tenant_id: $tenant->id,
            step: 'payment_configured',
            step_data: $validated
        );

        return response()->json(['success' => true]);
    }

    public function skipStep(Request $request, string $step)
    {
        $tenant = app('tenant');

        // Some steps can be skipped
        $skippableSteps = [
            'team_invited',
            'payment_configured',
            'branding_configured'
        ];

        if (!in_array($step, $skippableSteps)) {
            return response()->json(['error' => 'This step cannot be skipped'], 422);
        }

        TenantOnboardingStepCompleted::fire(
            tenant_id: $tenant->id,
            step: $step,
            step_data: ['skipped' => true]
        );

        return response()->json(['success' => true]);
    }

    private function getCurrentStep(Tenant $tenant): string
    {
        $progress = $tenant->onboarding_progress ?? [];

        $stepOrder = [
            'welcome_completed',
            'profile_setup',
            'branding_configured',
            'first_billboard_added',
            'first_client_added',
            'team_invited',
            'payment_configured',
            'first_booking_created'
        ];

        foreach ($stepOrder as $step) {
            if (!($progress[$step] ?? false)) {
                return $step;
            }
        }

        return 'completed';
    }

    private function getOnboardingSteps(): array
    {
        return [
            'welcome_completed' => [
                'title' => 'Welcome to AdPro',
                'description' => 'Get started with your billboard marketplace',
                'icon' => '👋',
                'skippable' => false,
            ],
            'profile_setup' => [
                'title' => 'Setup Your Profile',
                'description' => 'Tell us about your business',
                'icon' => '🏢',
                'skippable' => false,
            ],
            'branding_configured' => [
                'title' => 'Brand Your Platform',
                'description' => 'Customize colors and upload your logo',
                'icon' => '🎨',
                'skippable' => true,
            ],
            'first_billboard_added' => [
                'title' => 'Add Your First Billboard',
                'description' => 'Create your first billboard listing',
                'icon' => '📊',
                'skippable' => false,
            ],
            'first_client_added' => [
                'title' => 'Add a Client',
                'description' => 'Add your first client to the system',
                'icon' => '👥',
                'skippable' => false,
            ],
            'team_invited' => [
                'title' => 'Invite Your Team',
                'description' => 'Invite team members to collaborate',
                'icon' => '🤝',
                'skippable' => true,
            ],
            'payment_configured' => [
                'title' => 'Setup Payments',
                'description' => 'Configure payment methods',
                'icon' => '💳',
                'skippable' => true,
            ],
            'first_booking_created' => [
                'title' => 'Ready to Go!',
                'description' => 'Your account is fully set up',
                'icon' => '🚀',
                'skippable' => false,
            ],
        ];
    }
}