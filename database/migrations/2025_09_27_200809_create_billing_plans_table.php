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
        Schema::create('billing_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // trial, basic, pro, enterprise
            $table->string('display_name'); // Trial, Basic Plan, Pro Plan, Enterprise Plan
            $table->text('description');
            $table->decimal('price', 10, 2); // Monthly price
            $table->decimal('annual_price', 10, 2)->nullable(); // Annual price (optional)
            $table->integer('trial_days')->default(0); // Trial period in days
            $table->boolean('is_popular')->default(false); // Highlight as popular
            $table->boolean('is_active')->default(true); // Active/inactive
            $table->json('features')->nullable(); // JSON array of feature descriptions
            $table->json('limits')->nullable(); // JSON object with usage limits
            $table->integer('sort_order')->default(0); // Display order
            $table->timestamps();

            $table->unique('name');
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_plans');
    }
};
