<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['roles'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($request->role, function ($query, $role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            });

        $users = $query->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function show(User $user)
    {
        $user->load(['roles', 'permissions']);

        return Inertia::render('admin/users/Show', [
            'user' => $user,
            'loginHistory' => $this->getLoginHistory($user),
            'activityLog' => $this->getActivityLog($user),
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/users/Create', [
            'roles' => Role::all(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
            'status' => 'required|in:active,inactive,suspended',
            'send_welcome_email' => 'boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'],
            'email_verified_at' => now(),
        ]);

        $user->assignRole($validated['role']);

        if ($validated['send_welcome_email'] ?? false) {
            // TODO: Send welcome email
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log('Admin created user account');

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $user->load('roles');

        return Inertia::render('admin/users/Edit', [
            'user' => $user,
            'roles' => Role::all(['id', 'name']),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'status' => $validated['status'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Update role
        $user->syncRoles([$validated['role']]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log('Admin updated user account');

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        // Prevent deleting super admins
        if ($user->hasRole('super-admin')) {
            return back()->with('error', 'Cannot delete super admin users.');
        }

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Cannot delete your own account.');
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log('Admin deleted user account');

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function impersonate(User $user)
    {
        // Security check - only super admins can impersonate
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403);
        }

        // Log the impersonation
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log('Admin started impersonating user');

        // Store original user ID in session
        session(['original_user_id' => auth()->id()]);

        // Login as the target user
        auth()->login($user);

        return redirect()
            ->route('dashboard')
            ->with('info', "You are now impersonating {$user->name}. Click here to stop impersonation.");
    }

    public function stopImpersonation()
    {
        $originalUserId = session('original_user_id');

        if (!$originalUserId) {
            return redirect()->route('dashboard');
        }

        $originalUser = User::find($originalUserId);

        if (!$originalUser) {
            session()->forget('original_user_id');
            return redirect()->route('login');
        }

        // Log the end of impersonation
        activity()
            ->causedBy($originalUser)
            ->log('Admin stopped impersonating user');

        // Switch back to original user
        auth()->login($originalUser);
        session()->forget('original_user_id');

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Impersonation ended successfully.');
    }

    public function toggleTwoFactor(User $user)
    {
        $user->update([
            'two_factor_enabled' => !$user->two_factor_enabled,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log($user->two_factor_enabled ? 'Admin enabled 2FA for user' : 'Admin disabled 2FA for user');

        return back()->with('success',
            $user->two_factor_enabled
                ? '2FA enabled for user.'
                : '2FA disabled for user.'
        );
    }

    private function getLoginHistory(User $user): array
    {
        // This would typically come from a login_histories table
        // For now, return mock data
        return [
            [
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0...',
                'location' => 'Blantyre, Malawi',
                'login_at' => now()->subHours(2),
                'logout_at' => now()->subHour(),
            ],
            [
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0...',
                'location' => 'Blantyre, Malawi',
                'login_at' => now()->subDay(),
                'logout_at' => now()->subDay()->addHours(8),
            ],
        ];
    }

    private function getActivityLog(User $user): array
    {
        return activity()
            ->causedBy($user)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'description' => $activity->description,
                    'created_at' => $activity->created_at,
                    'properties' => $activity->properties,
                ];
            })
            ->toArray();
    }
}