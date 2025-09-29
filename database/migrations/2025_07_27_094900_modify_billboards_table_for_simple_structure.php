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
        // Determine driver to handle SQLite limitations
        $driver = null;
        try {
            $driver = DB::getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
        } catch (\Exception $e) {
            // If we can't detect the driver, default to null and be conservative
            $driver = null;
        }

        Schema::table('billboards', function (Blueprint $table) {
            // Check and drop foreign key constraints if they exist
            if (DB::getSchemaBuilder()->hasColumn('billboards', 'company_id')) {
                $table->dropForeign(['company_id']);
            }
            if (DB::getSchemaBuilder()->hasColumn('billboards', 'location_id')) {
                $table->dropForeign(['location_id']);
            }
        });

        Schema::table('billboards', function (Blueprint $table) use ($driver) {
            // Only drop columns that exist — but SQLite cannot drop columns safely via alter table,
            // so skip dropColumn on sqlite and let the rest of the migration add the new columns.
            if ($driver !== 'sqlite') {
                $columns = DB::getSchemaBuilder()->getColumnListing('billboards');
                $columnsToCheck = [
                    'uuid', 'code', 'location_id', 'base_price', 'currency_code',
                    'physical_status', 'latitude', 'longitude', 'site',
                    'is_active', 'meta_data', 'company_id'
                ];

                $columnsToRemove = array_intersect($columnsToCheck, $columns);

                if (!empty($columnsToRemove)) {
                    $table->dropColumn($columnsToRemove);
                }
            }

            // Add new columns if they don't exist
            $columns = DB::getSchemaBuilder()->getColumnListing('billboards');

            if (!in_array('location', $columns)) {
                $table->string('location')->after('name');
            }
            if (!in_array('price', $columns)) {
                $table->decimal('price', 10, 2)->after('size');
            }
            if (!in_array('status', $columns)) {
                $table->enum('status', ['available', 'occupied', 'maintenance'])->default('available')->after('price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billboards', function (Blueprint $table) {
            // Reverse the changes
            if (DB::getSchemaBuilder()->hasColumn('billboards', 'location')) {
                $table->dropColumn(['location']);
            }
            if (DB::getSchemaBuilder()->hasColumn('billboards', 'price')) {
                $table->dropColumn(['price']);
            }
            if (DB::getSchemaBuilder()->hasColumn('billboards', 'status')) {
                $table->dropColumn(['status']);
            }

            // Add back the original columns (simplified)
            if (!DB::getSchemaBuilder()->hasColumn('billboards', 'code')) {
                $table->string('code')->unique()->nullable();
            }
            if (!DB::getSchemaBuilder()->hasColumn('billboards', 'base_price')) {
                $table->decimal('base_price', 10, 2);
            }
            if (!DB::getSchemaBuilder()->hasColumn('billboards', 'physical_status')) {
                $table->string('physical_status');
            }
            if (!DB::getSchemaBuilder()->hasColumn('billboards', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }
};
