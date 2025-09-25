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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Foreign key relationships
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->uuid('booking_id')->nullable();
            $table->foreign('booking_id')->references('id')->on('bookings')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();

            // Payment details
            $table->string('provider'); // airtel_money, tnm_mpamba, card, bank_transfer, paychangu
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('MWK');
            $table->string('phone_number')->nullable(); // For mobile money payments
            $table->string('reference')->unique(); // Our internal reference
            $table->string('external_id')->nullable(); // PayChangu transaction ID

            // Status and tracking
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled',
                'refunded'
            ])->default('pending');

            $table->text('failure_reason')->nullable();

            // Provider response and metadata
            $table->json('provider_response')->nullable();
            $table->json('metadata')->nullable();

            // Timestamps for payment lifecycle
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('status_checked_at')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['tenant_id', 'status']);
            $table->index(['booking_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index(['provider', 'status']);
            $table->index(['external_id']);
            $table->index(['reference']);
            $table->index(['created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};