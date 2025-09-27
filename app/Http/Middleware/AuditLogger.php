<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Activitylog\Facades\Activity;

class AuditLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $response = $next($request);
        $endTime = microtime(true);

        $this->logActivity($request, $response, $endTime - $startTime);

        return $response;
    }

    /**
     * Log the activity based on the request and response.
     */
    private function logActivity(Request $request, Response $response, float $duration): void
    {
        $user = $request->user();
        $statusCode = $response->getStatusCode();

        // Only log significant activities
        if (!$this->shouldLog($request, $statusCode)) {
            return;
        }

        $properties = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status_code' => $statusCode,
            'duration' => round($duration * 1000, 2), // Convert to milliseconds
        ];

        // Add request data for POST/PUT/PATCH requests
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $properties['request_data'] = $this->sanitizeRequestData($request);
        }

        // Add tenant context if available
        if ($tenant = $request->route('tenant')) {
            $properties['tenant_id'] = $tenant->id;
            $properties['tenant_name'] = $tenant->name;
        }

        $description = $this->getActivityDescription($request, $statusCode);

        $activity = Activity::log($description)
            ->withProperties($properties);

        if ($user) {
            $activity->causedBy($user);
        }
    }

    /**
     * Determine if this request should be logged.
     */
    private function shouldLog(Request $request, int $statusCode): bool
    {
        // Don't log health checks, assets, or OPTIONS requests
        if (
            $request->is('health*') ||
            $request->is('api/health*') ||
            $request->is('_debugbar*') ||
            $request->method() === 'OPTIONS'
        ) {
            return false;
        }

        // Always log admin routes
        if ($request->routeIs('admin.*')) {
            return true;
        }

        // Log authentication-related routes
        if ($request->routeIs(['login', 'register', 'logout', 'password.*', 'two-factor.*'])) {
            return true;
        }

        // Log API endpoints
        if ($request->is('api/*')) {
            return true;
        }

        // Log failed requests
        if ($statusCode >= 400) {
            return true;
        }

        // Log POST/PUT/PATCH/DELETE requests
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return true;
        }

        return false;
    }

    /**
     * Generate a meaningful description for the activity.
     */
    private function getActivityDescription(Request $request, int $statusCode): string
    {
        if ($statusCode >= 500) {
            return 'Server error occurred';
        }

        if ($statusCode >= 400) {
            return 'Failed request';
        }

        if ($request->routeIs('admin.*')) {
            return 'Admin panel activity';
        }

        if ($request->routeIs('login')) {
            return 'User login attempt';
        }

        if ($request->routeIs('logout')) {
            return 'User logout';
        }

        if ($request->routeIs('register')) {
            return 'User registration attempt';
        }

        if ($request->is('api/*')) {
            return 'API request';
        }

        return 'Web request';
    }

    /**
     * Sanitize request data to remove sensitive information.
     */
    private function sanitizeRequestData(Request $request): array
    {
        $data = $request->all();

        // Remove sensitive fields
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'secret',
            'key',
            'api_key',
            'access_token',
            'refresh_token',
            'credit_card_number',
            'cvv',
            'ssn',
            'social_security_number',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }
}