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
        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->foreignId('billing_plan_id')->constrained()->onDelete('restrict');
            $table->string('status'); // active, cancelled, expired, suspended, trial
            $table->string('payment_status')->nullable(); // paid, pending, failed, refunded
            $table->string('paychangu_subscription_id')->nullable(); // PayChangu subscription ID
            $table->string('paychangu_customer_id')->nullable(); // PayChangu customer ID
            $table->decimal('amount', 10, 2); // Subscription amount
            $table->string('currency', 3)->default('ZMW'); // Currency code
            $table->string('interval')->default('monthly'); // monthly, yearly
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable(); // Additional data
            $table->timestamps();

            // Foreign key constraint handled via Eloquent relationships
            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'current_period_end']);
            $table->index('paychangu_subscription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
    }
};
