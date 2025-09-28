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
        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // analytics, team_management, advanced_reporting, etc.
            $table->string('display_name'); // Analytics Dashboard, Team Management, etc.
            $table->text('description');
            $table->string('icon')->nullable(); // Lucide icon name
            $table->string('category')->default('general'); // general, advanced, enterprise
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique('name');
            $table->index(['is_active', 'category', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_features');
    }
};
