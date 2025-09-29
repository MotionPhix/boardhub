<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Helper function to safely add indexes only if columns exist and index doesn't exist
        $addIndexSafely = function (string $table, array $columns, string $indexName) {
            if (!Schema::hasTable($table)) {
                return;
            }

            // Check if all columns exist
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    return;
                }
            }

            // Attempt to create the index and ignore failures (idempotent across DBs)
            try {
                Schema::table($table, function (Blueprint $tableSchema) use ($columns, $indexName) {
                    $tableSchema->index($columns, $indexName);
                });
            } catch (\Exception $e) {
                // Ignore exceptions (index may already exist or driver may not support compound indexes)
            }
        };

        // Billboards table - most queried entity
        $addIndexSafely('billboards', ['tenant_id', 'status', 'created_at'], 'billboards_tenant_status_created_idx');
        $addIndexSafely('billboards', ['tenant_id', 'location', 'status'], 'billboards_tenant_location_status_idx');
        $addIndexSafely('billboards', ['tenant_id', 'price', 'status'], 'billboards_tenant_price_status_idx');

        // Bookings table - critical for revenue tracking
        $addIndexSafely('bookings', ['tenant_id', 'start_date', 'end_date'], 'bookings_tenant_date_range_idx');
        $addIndexSafely('bookings', ['tenant_id', 'status', 'created_at'], 'bookings_tenant_status_created_idx');
        $addIndexSafely('bookings', ['tenant_id', 'client_id', 'status'], 'bookings_tenant_client_status_idx');

        // Payments table - financial data requires fast access
        $addIndexSafely('payments', ['tenant_id', 'status', 'created_at'], 'payments_tenant_status_created_idx');
        $addIndexSafely('payments', ['tenant_id', 'provider', 'status'], 'payments_tenant_provider_status_idx');
        $addIndexSafely('payments', ['tenant_id', 'external_id'], 'payments_tenant_external_id_idx');

        // Clients table - customer management
        $addIndexSafely('clients', ['tenant_id', 'created_at'], 'clients_tenant_created_idx');
        $addIndexSafely('clients', ['tenant_id', 'name'], 'clients_tenant_name_idx');

        // Users table - authentication and access control
        $addIndexSafely('users', ['tenant_id', 'email'], 'users_tenant_email_idx');
        $addIndexSafely('users', ['tenant_id', 'is_active'], 'users_tenant_active_idx');

        // Add indexes to other tenant-scoped tables
        $tenantTables = [
            'contracts',
            'locations',
            'media',
            'notifications',
            'notification_settings',
        ];

        foreach ($tenantTables as $table) {
            $addIndexSafely($table, ['tenant_id', 'created_at'], $table . '_tenant_created_idx');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Helper function to safely drop indexes
        $dropIndexSafely = function (string $table, string $indexName) {
            if (!Schema::hasTable($table)) {
                return;
            }

            try {
                Schema::table($table, function (Blueprint $tableSchema) use ($indexName) {
                    $tableSchema->dropIndex($indexName);
                });
            } catch (\Exception $e) {
                // Index might not exist, continue silently
            }
        };

        // Remove compound indexes
        $dropIndexSafely('billboards', 'billboards_tenant_status_created_idx');
        $dropIndexSafely('billboards', 'billboards_tenant_location_status_idx');
        $dropIndexSafely('billboards', 'billboards_tenant_price_status_idx');

        $dropIndexSafely('bookings', 'bookings_tenant_date_range_idx');
        $dropIndexSafely('bookings', 'bookings_tenant_status_created_idx');
        $dropIndexSafely('bookings', 'bookings_tenant_client_status_idx');

        $dropIndexSafely('payments', 'payments_tenant_status_created_idx');
        $dropIndexSafely('payments', 'payments_tenant_provider_status_idx');
        $dropIndexSafely('payments', 'payments_tenant_external_id_idx');

        $dropIndexSafely('clients', 'clients_tenant_created_idx');
        $dropIndexSafely('clients', 'clients_tenant_name_idx');

        $dropIndexSafely('users', 'users_tenant_email_idx');
        $dropIndexSafely('users', 'users_tenant_active_idx');

        // Remove indexes from other tables
        $tenantTables = [
            'contracts',
            'locations',
            'media',
            'notifications',
            'notification_settings',
        ];

        foreach ($tenantTables as $table) {
            $dropIndexSafely($table, $table . '_tenant_created_idx');
        }
    }
};
