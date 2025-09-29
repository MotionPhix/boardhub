<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TenantInvitation;
use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $tenant = app('tenant');

        $members = $tenant->memberships()
            ->with('user')
            ->when($request->search, function ($query, $search) {
                return $query->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->role, function ($query, $role) {
                return $query->where('role', $role);
            })
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $pendingInvitations = TenantInvitation::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total_members' => $tenant->memberships()->count(),
            'active_members' => $tenant->memberships()->where('status', 'active')->count(),
            'pending_invitations' => $pendingInvitations->count(),
            'owners' => $tenant->memberships()->where('role', 'owner')->count(),
            'admins' => $tenant->memberships()->where('role', 'admin')->count(),
            'members' => $tenant->memberships()->where('role', 'member')->count(),
        ];

        $filters = [
            'roles' => [
                'owner' => 'Owner',
                'admin' => 'Admin',
                'member' => 'Member'
            ],
            'statuses' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
                'suspended' => 'Suspended'
            ]
        ];

        return Inertia::render('tenant/team/Index', [
            'tenant' => $tenant,
            'members' => $members,
            'pendingInvitations' => $pendingInvitations,
            'stats' => $stats,
            'filters' => $filters,
            'queryParams' => $request->query()
        ]);
    }

    public function show(User $user)
    {
        $tenant = app('tenant');

        $membership = $tenant->memberships()
            ->where('user_id', $user->id)
            ->with('user')
            ->firstOrFail();

        // Get user's activity within this tenant
        $recentActivity = $this->getUserActivity($user, $tenant);

        return Inertia::render('tenant/team/Show', [
            'tenant' => $tenant,
            'member' => $membership,
            'recentActivity' => $recentActivity
        ]);
    }

    public function invite(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'role' => 'required|in:admin,member',
            'message' => 'nullable|string|max:500'
        ]);

        // Check if user already exists
        $existingUser = User::where('email', $validated['email'])->first();

        if ($existingUser) {
            // Check if already a member
            $existingMembership = $tenant->memberships()
                ->where('user_id', $existingUser->id)
                ->first();

            if ($existingMembership) {
                return back()->withErrors(['email' => 'This user is already a member of your organization.']);
            }
        }

        // Check for existing pending invitation
        $existingInvitation = TenantInvitation::where('tenant_id', $tenant->id)
            ->where('email', $validated['email'])
            ->where('status', 'pending')
            ->first();

        if ($existingInvitation) {
            return back()->withErrors(['email' => 'An invitation has already been sent to this email address.']);
        }

        // Create invitation
        $invitation = TenantInvitation::create([
            'tenant_id' => $tenant->id,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'message' => $validated['message'],
            'token' => Str::random(64),
            'invited_by' => auth()->id(),
            'status' => 'pending',
            'expires_at' => now()->addDays(7)
        ]);

        // Send invitation email
        // Mail::to($validated['email'])->send(new TeamInvitation($invitation));

        return back()->with('success', 'Invitation sent successfully!');
    }

    public function updateRole(Request $request, User $user)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'role' => 'required|in:owner,admin,member'
        ]);

        $membership = $tenant->memberships()
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Prevent changing your own role if you're the only owner
        if ($user->id === auth()->id() && $membership->role === 'owner') {
            $otherOwners = $tenant->memberships()
                ->where('role', 'owner')
                ->where('user_id', '!=', $user->id)
                ->count();

            if ($otherOwners === 0 && $validated['role'] !== 'owner') {
                return back()->withErrors(['error' => 'You cannot change your role as you are the only owner.']);
            }
        }

        $membership->update(['role' => $validated['role']]);

        return back()->with('success', 'Member role updated successfully!');
    }

    public function updateStatus(Request $request, User $user)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'status' => 'required|in:active,inactive,suspended',
            'reason' => 'nullable|string|max:500'
        ]);

        $membership = $tenant->memberships()
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Prevent suspending yourself
        if ($user->id === auth()->id() && $validated['status'] === 'suspended') {
            return back()->withErrors(['error' => 'You cannot suspend your own account.']);
        }

        $membership->update([
            'status' => $validated['status'],
            'status_reason' => $validated['reason']
        ]);

        $action = match($validated['status']) {
            'active' => 'activated',
            'inactive' => 'deactivated',
            'suspended' => 'suspended'
        };

        return back()->with('success', "Member {$action} successfully!");
    }

    public function removeMember(User $user)
    {
        $tenant = app('tenant');

        $membership = $tenant->memberships()
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Prevent removing yourself if you're the only owner
        if ($user->id === auth()->id() && $membership->role === 'owner') {
            $otherOwners = $tenant->memberships()
                ->where('role', 'owner')
                ->where('user_id', '!=', $user->id)
                ->count();

            if ($otherOwners === 0) {
                return back()->withErrors(['error' => 'You cannot remove yourself as you are the only owner.']);
            }
        }

        $membership->delete();

        return back()->with('success', 'Member removed successfully!');
    }

    public function resendInvitation(TenantInvitation $invitation)
    {
        $tenant = app('tenant');

        if ($invitation->tenant_id !== $tenant->id) {
            abort(403);
        }

        if ($invitation->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending invitations can be resent.']);
        }

        if ($invitation->expires_at->isPast()) {
            $invitation->update([
                'expires_at' => now()->addDays(7),
                'token' => Str::random(64)
            ]);
        }

        // Resend invitation email
        // Mail::to($invitation->email)->send(new TeamInvitation($invitation));

        return back()->with('success', 'Invitation resent successfully!');
    }

    public function cancelInvitation(TenantInvitation $invitation)
    {
        $tenant = app('tenant');

        if ($invitation->tenant_id !== $tenant->id) {
            abort(403);
        }

        $invitation->update(['status' => 'cancelled']);

        return back()->with('success', 'Invitation cancelled successfully!');
    }

    public function acceptInvitation(Request $request, $token)
    {
        $invitation = TenantInvitation::where('token', $token)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $user = auth()->user();

        // If user is not authenticated, redirect to register/login
        if (!$user) {
            return redirect()->route('register')->with([
                'invitation_token' => $token,
                'email' => $invitation->email
            ]);
        }

        // Verify email matches
        if ($user->email !== $invitation->email) {
            return back()->withErrors(['error' => 'This invitation is for a different email address.']);
        }

        // Check if already a member
        $existingMembership = $invitation->tenant->memberships()
            ->where('user_id', $user->id)
            ->first();

        if ($existingMembership) {
            $invitation->update(['status' => 'accepted']);
            return redirect()->route('tenant.dashboard', ['tenant' => $invitation->tenant->uuid])
                ->with('success', 'You are already a member of this organization.');
        }

        // Create membership
        Membership::create([
            'tenant_id' => $invitation->tenant_id,
            'user_id' => $user->id,
            'role' => $invitation->role,
            'status' => Membership::STATUS_ACTIVE
        ]);

        $invitation->update(['status' => 'accepted']);

        return redirect()->route('tenant.dashboard', ['tenant' => $invitation->tenant->uuid])
            ->with('success', 'Welcome to the team!');
    }

    public function permissions(Request $request)
    {
        $tenant = app('tenant');

        $roles = [
            'owner' => [
                'description' => 'Full access to all features and settings',
                'permissions' => [
                    'Manage team members and roles',
                    'Access all billboards and bookings',
                    'Manage organization settings',
                    'View all analytics and reports',
                    'Billing and subscription management'
                ]
            ],
            'admin' => [
                'description' => 'Manage operations and team members',
                'permissions' => [
                    'Manage team members (except owners)',
                    'Access all billboards and bookings',
                    'View analytics and reports',
                    'Manage organization settings (limited)'
                ]
            ],
            'member' => [
                'description' => 'Access assigned billboards and basic features',
                'permissions' => [
                    'View assigned billboards',
                    'Manage assigned bookings',
                    'View basic analytics',
                    'Update own profile'
                ]
            ]
        ];

        return Inertia::render('tenant/team/Permissions', [
            'tenant' => $tenant,
            'roles' => $roles
        ]);
    }

    private function getUserActivity(User $user, $tenant): array
    {
        // This would typically fetch from an activity log table
        // For now, return sample activity data
        return [
            [
                'action' => 'Updated billboard pricing',
                'target' => 'Downtown Billboard #1',
                'timestamp' => now()->subHours(2)->format('M d, Y g:i A')
            ],
            [
                'action' => 'Approved booking request',
                'target' => 'Booking #1234',
                'timestamp' => now()->subDays(1)->format('M d, Y g:i A')
            ],
            [
                'action' => 'Logged in',
                'target' => null,
                'timestamp' => now()->subDays(3)->format('M d, Y g:i A')
            ]
        ];
    }
}
