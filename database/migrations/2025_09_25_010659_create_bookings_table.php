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
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('billboard_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->decimal('requested_price', 10, 2);
            $table->decimal('final_price', 10, 2)->nullable();
            $table->text('campaign_details')->nullable();
            $table->enum('status', ['requested', 'confirmed', 'rejected', 'cancelled', 'completed'])->default('requested');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('price_negotiations')->nullable();
            $table->json('status_history')->nullable();
            $table->timestamps();

            $table->index(['billboard_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
