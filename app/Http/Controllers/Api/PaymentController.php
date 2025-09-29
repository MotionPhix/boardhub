<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Models\Payment;
use App\Models\Tenant;
use App\Services\PayChanguService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function __construct(
        private PayChanguService $payChanguService
    ) {}

    /**
     * Get supported payment providers for tenant
     */
    public function getProviders(string $tenantUuid)
    {
        $tenant = app()->bound('tenant') ? app('tenant') : Tenant::where('uuid', $tenantUuid)->firstOrFail();
        $providers = $this->payChanguService->getSupportedProviders($tenant);

        return response()->json([
            'success' => true,
            'data' => [
                'providers' => $providers,
                'default_currency' => 'MWK',
                'supported_currencies' => ['MWK', 'USD'],
            ],
        ]);
    }

    /**
     * Process a new payment
     */
    public function processPayment(PaymentRequest $request, string $tenantUuid)
    {
        $tenant = app()->bound('tenant') ? app('tenant') : Tenant::where('uuid', $tenantUuid)->firstOrFail();
        $validated = $request->validated();

        try {
            $paymentData = array_merge($validated, [
                'tenant_id' => $tenant->id,
            ]);

            $result = $this->payChanguService->processPayment($paymentData);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Payment initiated successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment initiation failed',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Check payment status
     */
    public function checkStatus(string $tenantUuid, string $paymentUuid)
    {
        $tenant = app()->bound('tenant') ? app('tenant') : Tenant::where('uuid', $tenantUuid)->firstOrFail();
        $payment = $tenant->payments()->where('uuid', $paymentUuid)->firstOrFail();

        try {
            $status = $this->payChanguService->checkPaymentStatus($payment);

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_id' => $payment->uuid,
                    'status' => $payment->fresh()->status,
                    'provider_status' => $status['status'] ?? 'unknown',
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'reference' => $payment->reference,
                    'created_at' => $payment->created_at->toISOString(),
                    'updated_at' => $payment->updated_at->toISOString(),
                    'completed_at' => $payment->completed_at?->toISOString(),
                    'duration' => $payment->getDuration(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payment history for tenant
     */
    public function getHistory(Request $request, string $tenantUuid)
    {
        $tenant = app()->bound('tenant') ? app('tenant') : Tenant::where('uuid', $tenantUuid)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:pending,processing,completed,failed,cancelled',
            'provider' => 'nullable|in:airtel_money,tnm_mpamba,bank_transfer,card,paychangu',
            'booking_id' => 'nullable|string|exists:bookings,id',
            'client_id' => 'nullable|integer|exists:clients,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $filters = $validator->validated();

        $query = $tenant->payments()
            ->with(['booking', 'client'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['provider'])) {
            $query->where('provider', $filters['provider']);
        }

        if (isset($filters['booking_id'])) {
            $query->where('booking_id', $filters['booking_id']);
        }

        if (isset($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $perPage = $filters['per_page'] ?? 20;
        $payments = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'payments' => $payments->items(),
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                    'has_more' => $payments->hasMorePages(),
                ],
                'summary' => $this->getPaymentsSummary($tenant, $filters),
            ],
        ]);
    }

    /**
     * Get payment analytics
     */
    public function getAnalytics(Request $request, string $tenantUuid)
    {
        $tenant = app()->bound('tenant') ? app('tenant') : Tenant::where('uuid', $tenantUuid)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'period' => 'nullable|in:today,week,month,quarter,year',
            'include_breakdown' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $period = $request->input('period', 'month');
        $includeBreakdown = $request->boolean('include_breakdown', true);

        $analytics = $this->generatePaymentAnalytics($tenant, $period, $includeBreakdown);

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'meta' => [
                'period' => $period,
                'generated_at' => now()->toISOString(),
                'currency' => 'MWK',
            ],
        ]);
    }

    /**
     * Handle payment webhook/callback
     */
    public function handleWebhook(Request $request, string $provider)
    {
        try {
            $callbackData = $request->all();

            $result = $this->payChanguService->handlePaymentCallback($callbackData, $provider);

            if ($result['success']) {
                return response()->json(['status' => 'success'], 200);
            } else {
                return response()->json(['status' => 'error'], 400);
            }

        } catch (\Exception $e) {
            \Log::error('Payment webhook handling failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Retry failed payment
     */
    public function retryPayment(string $tenantUuid, string $paymentUuid)
    {
        $tenant = app()->bound('tenant') ? app('tenant') : Tenant::where('uuid', $tenantUuid)->firstOrFail();
        $payment = $tenant->payments()->where('uuid', $paymentUuid)->firstOrFail();

        if (! $payment->isFailed()) {
            return response()->json([
                'success' => false,
                'message' => 'Only failed payments can be retried',
            ], 422);
        }

        try {
            // Create new payment with same details
            $paymentData = [
                'tenant_id' => $payment->tenant_id,
                'booking_id' => $payment->booking_id,
                'client_id' => $payment->client_id,
                'provider' => $payment->provider,
                'amount' => $payment->amount,
                'phone_number' => $payment->phone_number,
                'reference' => $payment->reference.'_RETRY_'.time(),
            ];

            $result = $this->payChanguService->processPayment($paymentData);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Payment retry initiated successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment retry failed',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel pending payment
     */
    public function cancelPayment(string $tenantUuid, string $paymentUuid)
    {
        $tenant = app()->bound('tenant') ? app('tenant') : Tenant::where('uuid', $tenantUuid)->firstOrFail();
        $payment = $tenant->payments()->where('uuid', $paymentUuid)->firstOrFail();

        if (! $payment->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending payments can be cancelled',
            ], 422);
        }

        $payment->update([
            'status' => Payment::STATUS_CANCELLED,
            'failure_reason' => 'Cancelled by user',
            'failed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment cancelled successfully',
            'data' => [
                'payment_id' => $payment->uuid,
                'status' => Payment::STATUS_CANCELLED,
            ],
        ]);
    }

    private function getPaymentsSummary($tenant, array $filters): array
    {
        $baseQuery = $tenant->payments();

        // Apply same filters as main query
        if (isset($filters['date_from'])) {
            $baseQuery->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $baseQuery->whereDate('created_at', '<=', $filters['date_to']);
        }

        return [
            'total_payments' => $baseQuery->count(),
            'completed_payments' => $baseQuery->clone()->completed()->count(),
            'pending_payments' => $baseQuery->clone()->pending()->count(),
            'failed_payments' => $baseQuery->clone()->failed()->count(),
            'total_amount' => $baseQuery->clone()->completed()->sum('amount'),
            'success_rate' => $this->calculateSuccessRate($baseQuery),
        ];
    }

    private function calculateSuccessRate($query): float
    {
        $total = $query->count();
        if ($total === 0) {
            return 0;
        }

        $completed = $query->clone()->completed()->count();

        return round(($completed / $total) * 100, 2);
    }

    private function generatePaymentAnalytics($tenant, string $period, bool $includeBreakdown): array
    {
        $query = $tenant->payments();

        // Apply period filter
        $query = match ($period) {
            'today' => $query->whereDate('created_at', today()),
            'week' => $query->where('created_at', '>=', now()->subWeek()),
            'month' => $query->where('created_at', '>=', now()->subMonth()),
            'quarter' => $query->where('created_at', '>=', now()->subQuarter()),
            'year' => $query->where('created_at', '>=', now()->subYear()),
            default => $query->where('created_at', '>=', now()->subMonth()),
        };

        $analytics = [
            'summary' => [
                'total_transactions' => $query->count(),
                'total_amount' => $query->completed()->sum('amount'),
                'success_rate' => $this->calculateSuccessRate($query),
                'average_transaction_value' => $query->completed()->avg('amount') ?? 0,
            ],
            'status_breakdown' => [
                'completed' => $query->clone()->completed()->count(),
                'pending' => $query->clone()->pending()->count(),
                'failed' => $query->clone()->failed()->count(),
                'cancelled' => $query->clone()->where('status', Payment::STATUS_CANCELLED)->count(),
            ],
        ];

        if ($includeBreakdown) {
            $analytics['provider_breakdown'] = [
                'airtel_money' => [
                    'count' => $query->clone()->byProvider('airtel_money')->count(),
                    'amount' => $query->clone()->byProvider('airtel_money')->completed()->sum('amount'),
                ],
                'tnm_mpamba' => [
                    'count' => $query->clone()->byProvider('tnm_mpamba')->count(),
                    'amount' => $query->clone()->byProvider('tnm_mpamba')->completed()->sum('amount'),
                ],
            ];

            $analytics['daily_trends'] = $this->getDailyTrends($query, $period);
        }

        return $analytics;
    }

    private function getDailyTrends($query, string $period): array
    {
        $days = match ($period) {
            'today' => 1,
            'week' => 7,
            'month' => 30,
            'quarter' => 90,
            'year' => 365,
            default => 30,
        };

        $trends = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayData = $query->clone()->whereDate('created_at', $date->toDateString());

            $trends[] = [
                'date' => $date->toDateString(),
                'transactions' => $dayData->count(),
                'amount' => $dayData->completed()->sum('amount') ?? 0,
                'success_rate' => $this->calculateSuccessRate($dayData),
            ];
        }

        return $trends;
    }
}
