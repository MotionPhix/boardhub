<?php

namespace App\Http\Controllers;

use App\Models\BillingPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function index(Request $request, string $plan): Response
    {
        // Get the billing plan
        $billingPlan = BillingPlan::where('name', $plan)->firstOrFail();

        // Get pending organization data from session
        $pendingOrganization = session('pending_organization');

        if (!$pendingOrganization) {
            return redirect()->route('organizations.create')
                ->with('error', 'Session expired. Please start over.');
        }

        // Verify plan matches
        if ($pendingOrganization['plan'] !== $plan) {
            return redirect()->route('organizations.create')
                ->with('error', 'Plan mismatch. Please start over.');
        }

        return Inertia::render('checkout/Index', [
            'billingPlan' => $billingPlan,
            'pendingOrganization' => $pendingOrganization,
            'paychanguPublicKey' => config('services.paychangu.public_key'),
        ]);
    }

    public function createPayment(Request $request)
    {
        $validated = $request->validate([
            'plan' => 'required|string|exists:billing_plans,name',
            'payment_method' => 'required|string|in:card,mobile_money',
            'billing_cycle' => 'required|string|in:monthly,annually',
        ]);

        // Get billing plan and pending organization
        $billingPlan = BillingPlan::where('name', $validated['plan'])->firstOrFail();
        $pendingOrganization = session('pending_organization');

        if (!$pendingOrganization) {
            return response()->json(['error' => 'Session expired'], 400);
        }

        // Calculate amount based on billing cycle
        $amount = $validated['billing_cycle'] === 'annually'
            ? $billingPlan->annual_price
            : $billingPlan->price;

        // Create PayChangu payment request
        $paymentData = $this->createPayChanguPayment([
            'amount' => $amount,
            'currency' => 'ZMW',
            'email' => $request->user()->email,
            'customer_name' => $request->user()->name,
            'payment_method' => $validated['payment_method'],
            'billing_cycle' => $validated['billing_cycle'],
            'plan_name' => $billingPlan->display_name,
            'organization_name' => $pendingOrganization['name'],
            'metadata' => [
                'user_id' => $request->user()->id,
                'billing_plan_id' => $billingPlan->id,
                'billing_cycle' => $validated['billing_cycle'],
                'pending_organization' => $pendingOrganization,
            ]
        ]);

        if (!$paymentData['success']) {
            return response()->json([
                'error' => 'Payment initialization failed',
                'message' => $paymentData['message'] ?? 'Unknown error'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'payment_url' => $paymentData['payment_url'],
            'reference' => $paymentData['reference'],
        ]);
    }

    public function handleCallback(Request $request)
    {
        $validated = $request->validate([
            'reference' => 'required|string',
            'status' => 'required|string',
        ]);

        // Verify payment with PayChangu
        $paymentStatus = $this->verifyPayChanguPayment($validated['reference']);

        if (!$paymentStatus['success']) {
            return redirect()->route('checkout.failure')
                ->with('error', 'Payment verification failed.');
        }

        $paymentData = $paymentStatus['data'];

        // Check if payment was successful
        if ($paymentData['status'] !== 'successful') {
            return redirect()->route('checkout.failure')
                ->with('error', 'Payment was not successful.');
        }

        // Create organization and subscription
        try {
            $organization = $this->createOrganizationAfterPayment($paymentData);

            return redirect()->route('checkout.success')
                ->with('success', 'Payment successful! Your organization has been created.');
        } catch (\Exception $e) {
            \Log::error('Organization creation failed after payment', [
                'reference' => $validated['reference'],
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('checkout.failure')
                ->with('error', 'Payment was successful but organization creation failed. Our team will contact you shortly.');
        }
    }

    public function success(): Response
    {
        return Inertia::render('checkout/Success');
    }

    public function failure(): Response
    {
        return Inertia::render('checkout/Failure');
    }

    private function createPayChanguPayment(array $data): array
    {
        try {
            $payload = [
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'email' => $data['email'],
                'first_name' => explode(' ', $data['customer_name'])[0],
                'last_name' => explode(' ', $data['customer_name'], 2)[1] ?? '',
                'callback_url' => route('checkout.callback'),
                'return_url' => route('checkout.success'),
                'tx_ref' => 'adpro_' . Str::uuid(),
                'customization' => [
                    'title' => 'AdPro Subscription',
                    'description' => "Subscription to {$data['plan_name']} for {$data['organization_name']}",
                    'logo' => config('app.url') . '/images/logo.png',
                ],
                'meta' => $data['metadata'],
            ];

            $response = \Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.paychangu.secret_key'),
                'Content-Type' => 'application/json',
            ])->post(config('services.paychangu.base_url') . '/payments', $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                return [
                    'success' => true,
                    'payment_url' => $responseData['data']['link'],
                    'reference' => $payload['tx_ref'],
                ];
            }

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Payment initialization failed',
            ];

        } catch (\Exception $e) {
            \Log::error('PayChangu payment creation failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'success' => false,
                'message' => 'Payment service unavailable',
            ];
        }
    }

    private function verifyPayChanguPayment(string $reference): array
    {
        try {
            $response = \Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.paychangu.secret_key'),
            ])->get(config('services.paychangu.base_url') . "/payments/{$reference}/verify");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'],
                ];
            }

            return [
                'success' => false,
                'message' => 'Payment verification failed',
            ];

        } catch (\Exception $e) {
            \Log::error('PayChangu payment verification failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Verification service unavailable',
            ];
        }
    }

    private function createOrganizationAfterPayment(array $paymentData): Tenant
    {
        $metadata = $paymentData['meta'];
        $pendingOrganization = $metadata['pending_organization'];
        $billingPlanId = $metadata['billing_plan_id'];
        $billingCycle = $metadata['billing_cycle'];
        $userId = $metadata['user_id'];

        // Get user and billing plan
        $user = \App\Models\User::findOrFail($userId);
        $billingPlan = BillingPlan::findOrFail($billingPlanId);

        // Create organization
        $tenant = Tenant::create([
            'id' => Str::uuid(),
            'name' => $pendingOrganization['name'],
            'description' => $pendingOrganization['description'],
            'slug' => $pendingOrganization['slug'],
            'subdomain' => $pendingOrganization['subdomain'],
            'plan' => $pendingOrganization['plan'],
            'settings' => $pendingOrganization['settings'],
            'status' => 'active',
        ]);

        // Calculate subscription dates
        $currentPeriodStart = now();
        $currentPeriodEnd = $billingCycle === 'annually'
            ? $currentPeriodStart->copy()->addYear()
            : $currentPeriodStart->copy()->addMonth();

        // Create subscription
        TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'billing_plan_id' => $billingPlan->id,
            'status' => 'active',
            'payment_status' => 'paid',
            'paychangu_subscription_id' => $paymentData['id'],
            'paychangu_customer_id' => $paymentData['customer']['id'] ?? null,
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'],
            'interval' => $billingCycle === 'annually' ? 'yearly' : 'monthly',
            'current_period_start' => $currentPeriodStart,
            'current_period_end' => $currentPeriodEnd,
        ]);

        // Add user as organization owner
        $user->joinTenant($tenant->id, Membership::ROLE_OWNER);

        // Clear session data
        session()->forget('pending_organization');

        return $tenant;
    }
}
