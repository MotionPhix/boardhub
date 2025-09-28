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
        Schema::create('plan_feature_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_feature_id')->constrained()->onDelete('cascade');
            $table->boolean('is_enabled')->default(true);
            $table->json('limits')->nullable(); // Feature-specific limits (e.g., max_campaigns, max_team_members)
            $table->timestamps();

            $table->unique(['billing_plan_id', 'plan_feature_id']);
            $table->index(['billing_plan_id', 'is_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_feature_rules');
    }
};
