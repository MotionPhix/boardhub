<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function create(): Response
    {
        $billingPlans = \App\Models\BillingPlan::active()
            ->ordered()
            ->get();

        return Inertia::render('tenant/organizations/Create', [
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

        return redirect()->route('tenants.switch')->with('success', 'Organization created successfully! Your trial period has started.');
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
}
