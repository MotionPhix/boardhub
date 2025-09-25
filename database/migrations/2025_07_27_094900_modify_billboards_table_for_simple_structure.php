<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('billboards', function (Blueprint $table) {
            // Check and drop foreign key constraints if they exist
            if (DB::getSchemaBuilder()->hasColumn('billboards', 'company_id')) {
                $table->dropForeign(['company_id']);
            }
            if (DB::getSchemaBuilder()->hasColumn('billboards', 'location_id')) {
                $table->dropForeign(['location_id']);
            }
        });

        Schema::table('billboards', function (Blueprint $table) {
            // Only drop columns that exist
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

            // Add new columns if they don't exist
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
            $table->dropColumn(['location', 'price', 'status']);

            // Add back the original columns (simplified)
            $table->string('code')->unique()->nullable();
            $table->decimal('base_price', 10, 2);
            $table->string('physical_status');
            $table->boolean('is_active')->default(true);
        });
    }
};
