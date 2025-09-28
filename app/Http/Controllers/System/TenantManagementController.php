<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TenantManagementController extends Controller
{
    public function index(Request $request)
    {
        // Log who is accessing this system admin page
        $user = $request->user();
        logger()->info('System TenantManagementController accessed', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'tenant_id' => $user->tenant_id,
            'is_super_admin' => $user->isSuperAdmin(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $tenants = Tenant::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->withCount('users')
            ->latest()
            ->paginate(15);

        return Inertia::render('system/tenants/Index', [
            'tenants' => $tenants->through(function ($tenant) {
                return [
                    'uuid' => $tenant->uuid,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'is_active' => $tenant->is_active,
                    'created_at' => $tenant->created_at->format('M d, Y'),
                    'users_count' => $tenant->users_count,
                ];
            }),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['users' => function ($query) {
            $query->with('memberships')->latest();
        }]);

        $stats = [
            'total_users' => $tenant->users()->count(),
            'active_memberships' => $tenant->memberships()->active()->count(),
            'pending_invitations' => $tenant->memberships()->pending()->count(),
        ];

        return Inertia::render('system/tenants/Show', [
            'tenant' => $tenant,
            'stats' => $stats,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tenants',
            'subdomain' => 'nullable|string|max:255|unique:tenants',
            'settings' => 'nullable|array',
        ]);

        $validated['uuid'] = Str::uuid();
        $validated['status'] = 'active';

        $tenant = Tenant::create($validated);

        return redirect()->route('system.tenants.show', $tenant)
            ->with('success', 'Tenant created successfully.');
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tenants,slug,' . $tenant->id,
            'subdomain' => 'nullable|string|max:255|unique:tenants,subdomain,' . $tenant->id,
            'status' => 'required|in:active,inactive,suspended',
            'settings' => 'nullable|array',
        ]);

        $tenant->update($validated);

        return back()->with('success', 'Tenant updated successfully.');
    }

    public function destroy(Tenant $tenant)
    {
        // Soft delete or hard delete based on business rules
        $tenant->delete();

        return redirect()->route('system.tenants.index')
            ->with('success', 'Tenant deleted successfully.');
    }
}