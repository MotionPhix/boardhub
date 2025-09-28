<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Tenant-specific channels
Broadcast::channel('tenant.{tenantId}.dashboard', function ($user, $tenantId) {
    return $user->tenants()->where('tenants.id', $tenantId)->exists();
});

Broadcast::channel('tenant.{tenantId}.billboards', function ($user, $tenantId) {
    return $user->tenants()->where('tenants.id', $tenantId)->exists();
});

Broadcast::channel('tenant.{tenantId}.bookings', function ($user, $tenantId) {
    return $user->tenants()->where('tenants.id', $tenantId)->exists();
});

Broadcast::channel('tenant.{tenantId}.notifications', function ($user, $tenantId) {
    return $user->tenants()->where('tenants.id', $tenantId)->exists();
});

// User-specific notification channel
Broadcast::channel('user.{userId}.notifications', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Billboard-specific channels
Broadcast::channel('billboard.{billboardId}', function ($user, $billboardId) {
    // User should have access to tenant that owns the billboard
    return \App\Models\Billboard::where('id', $billboardId)
        ->whereHas('tenant', function ($query) use ($user) {
            $query->whereHas('users', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        })->exists();
});

// Booking-specific channels
Broadcast::channel('booking.{bookingId}', function ($user, $bookingId) {
    // User should have access to tenant that owns the booking
    return \App\Models\Booking::where('id', $bookingId)
        ->whereHas('tenant', function ($query) use ($user) {
            $query->whereHas('users', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        })->exists();
});
