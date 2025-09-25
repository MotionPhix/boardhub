<?php

use App\Models\Tenant;

// Simple test to verify multi-tenant system
$tenants = Tenant::all();

echo "Multi-Tenant System Status:\n";
echo "==========================\n";
echo "Total tenants: " . $tenants->count() . "\n\n";

foreach ($tenants as $tenant) {
    echo "Tenant: {$tenant->name}\n";
    echo "UUID: {$tenant->uuid}\n";
    echo "Slug: {$tenant->slug}\n";
    echo "Subdomain: {$tenant->subdomain}\n";
    echo "URL: /t/{$tenant->uuid}\n";
    echo "Admin URL: /t/{$tenant->uuid}/admin\n";
    echo "---\n";
}

echo "\nSystem URLs:\n";
echo "Super Admin Panel: /super-admin\n";
echo "Tenant Selection: /select-tenant\n";
