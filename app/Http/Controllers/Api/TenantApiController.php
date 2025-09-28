<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantApiController extends Controller
{
    public function userTenants(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get tenants the user has access to
        $tenants = Tenant::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with(['users' => function ($query) use ($user) {
            $query->where('user_id', $user->id);
        }])->get();

        $tenantData = $tenants->map(function ($tenant) {
            return [
                'id' => $tenant->id,
                'uuid' => $tenant->uuid,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'status' => $tenant->status,
                'created_at' => $tenant->created_at,
                'updated_at' => $tenant->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'tenants' => $tenantData,
        ]);
    }
}