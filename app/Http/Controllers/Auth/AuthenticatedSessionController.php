<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function securityCheck(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
            'action' => 'required|string|in:sensitive-operation,delete-account,security-settings',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            RateLimiter::hit('security-check:' . $user->id, 300);

            return response()->json([
                'success' => false,
                'message' => 'Invalid password',
            ], 401);
        }

        // Reset rate limiter on successful authentication
        RateLimiter::clear('security-check:' . $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Security check passed',
            'expires_at' => now()->addMinutes(10)->toISOString(),
        ]);
    }

    public function activeSessions(Request $request): Response
    {
        $user = $request->user();

        // Get active sessions from database or cache
        $sessions = $user->sessions()
            ->where('last_activity', '>', now()->subDays(30))
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'user_agent' => $session->user_agent,
                    'last_activity' => $session->last_activity,
                    'is_current' => $session->id === session()->getId(),
                ];
            });

        return Inertia::render('Auth/ActiveSessions', [
            'sessions' => $sessions,
        ]);
    }

    public function revokeSession(Request $request, string $sessionId): JsonResponse
    {
        $user = $request->user();

        $session = $user->sessions()->find($sessionId);

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
            ], 404);
        }

        $session->delete();

        return response()->json([
            'success' => true,
            'message' => 'Session revoked successfully',
        ]);
    }

    public function revokeAllSessions(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentSessionId = session()->getId();

        // Delete all sessions except current one
        $user->sessions()
            ->where('id', '!=', $currentSessionId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'All other sessions revoked successfully',
        ]);
    }

    public function securitySettings(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Auth/SecuritySettings', [
            'user' => $user->only(['id', 'name', 'email', 'two_factor_enabled', 'created_at']),
            'security_settings' => [
                'two_factor_enabled' => $user->two_factor_enabled ?? false,
                'last_password_change' => $user->password_changed_at,
                'login_notifications' => $user->login_notifications ?? true,
                'security_notifications' => $user->security_notifications ?? true,
            ],
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed|different:current_password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 401);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
            'password_changed_at' => now(),
        ]);

        // Optionally revoke all other sessions
        if ($request->boolean('revoke_other_sessions')) {
            $currentSessionId = session()->getId();
            $user->sessions()
                ->where('id', '!=', $currentSessionId)
                ->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    public function loginHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get login history from activity log or dedicated table
        $loginHistory = $user->activities()
            ->where('description', 'login')
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get()
            ->map(function ($activity) {
                return [
                    'ip_address' => $activity->properties['ip'] ?? 'Unknown',
                    'user_agent' => $activity->properties['user_agent'] ?? 'Unknown',
                    'location' => $activity->properties['location'] ?? 'Unknown',
                    'timestamp' => $activity->created_at,
                    'success' => $activity->properties['success'] ?? true,
                ];
            });

        return response()->json([
            'success' => true,
            'history' => $loginHistory,
        ]);
    }

    public function downloadData(Request $request): JsonResponse
    {
        $user = $request->user();

        // Generate user data export
        $userData = [
            'profile' => $user->only(['name', 'email', 'created_at', 'updated_at']),
            'login_history' => $user->activities()
                ->where('description', 'login')
                ->get()
                ->map(function ($activity) {
                    return [
                        'ip_address' => $activity->properties['ip'] ?? 'Unknown',
                        'timestamp' => $activity->created_at,
                    ];
                }),
            'generated_at' => now()->toISOString(),
        ];

        return response()->json([
            'success' => true,
            'data' => $userData,
            'download_url' => route('auth.security.download-data'),
        ]);
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
            'confirmation' => 'required|string|in:DELETE',
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
                'message' => 'Password is incorrect',
            ], 401);
        }

        // Log account deletion
        activity()
            ->performedOn($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('Account deleted');

        // Delete user account
        $user->delete();

        // Log out and invalidate session
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully',
        ]);
    }

    public function securityNotifications(Request $request): Response
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->where('type', 'like', '%Security%')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Auth/SecurityNotifications', [
            'notifications' => $notifications,
        ]);
    }

    public function acknowledgeNotification(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();

        $notification = $user->notifications()->find($notificationId);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification acknowledged',
        ]);
    }

    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->notifications()
            ->where('type', 'like', '%Security%')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All security notifications marked as read',
        ]);
    }

    public function reportIncident(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:suspicious-activity,unauthorized-access,data-breach,other',
            'description' => 'required|string|max:1000',
            'severity' => 'required|string|in:low,medium,high,critical',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Log security incident
        activity()
            ->performedOn($user)
            ->withProperties([
                'type' => $request->type,
                'description' => $request->description,
                'severity' => $request->severity,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log('Security incident reported');

        return response()->json([
            'success' => true,
            'message' => 'Security incident reported successfully',
            'incident_id' => Str::uuid(),
        ]);
    }

    public function securityStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        $status = [
            'overall_score' => $this->calculateSecurityScore($user),
            'two_factor_enabled' => $user->two_factor_enabled ?? false,
            'recent_suspicious_activity' => $this->hasRecentSuspiciousActivity($user),
            'password_strength' => 'strong', // This would be calculated based on password complexity
            'last_security_scan' => now()->subDays(7)->toISOString(),
            'recommendations' => $this->getSecurityRecommendations($user),
        ];

        return response()->json([
            'success' => true,
            'status' => $status,
        ]);
    }

    private function calculateSecurityScore(User $user): int
    {
        $score = 50; // Base score

        if ($user->two_factor_enabled) {
            $score += 30;
        }

        if ($user->password_changed_at && $user->password_changed_at->gt(now()->subMonths(3))) {
            $score += 10;
        }

        if (!$this->hasRecentSuspiciousActivity($user)) {
            $score += 10;
        }

        return min($score, 100);
    }

    private function hasRecentSuspiciousActivity(User $user): bool
    {
        return $user->activities()
            ->where('description', 'like', '%suspicious%')
            ->where('created_at', '>', now()->subDays(30))
            ->exists();
    }

    private function getSecurityRecommendations(User $user): array
    {
        $recommendations = [];

        if (!$user->two_factor_enabled) {
            $recommendations[] = [
                'type' => 'two_factor',
                'title' => 'Enable Two-Factor Authentication',
                'description' => 'Add an extra layer of security to your account',
                'priority' => 'high',
            ];
        }

        if (!$user->password_changed_at || $user->password_changed_at->lt(now()->subMonths(6))) {
            $recommendations[] = [
                'type' => 'password_change',
                'title' => 'Update Your Password',
                'description' => 'Consider changing your password regularly',
                'priority' => 'medium',
            ];
        }

        return $recommendations;
    }
}