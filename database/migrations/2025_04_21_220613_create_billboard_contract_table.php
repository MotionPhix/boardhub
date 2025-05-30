<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('billboard_contract', function (Blueprint $table) {
      $table->id();
      $table->foreignId('billboard_id')->constrained()->onDelete('cascade');
      $table->foreignId('contract_id')->constrained()->onDelete('cascade');

      // Pricing information
      $table->decimal('billboard_base_price', 10, 2);
      $table->decimal('billboard_discount_amount', 10, 2)->default(0);
      $table->decimal('billboard_final_price', 10, 2);

      // Status and notes
      $table->enum('booking_status', [
        'pending', 'confirmed', 'in_use', 'completed', 'cancelled'
      ])->default('pending');

      $table->text('notes')->nullable();

      $table->timestamps();

      // Indexes and constraints
      $table->unique(['billboard_id', 'contract_id']);
      $table->index(['contract_id', 'booking_status']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('billboard_contract');
  }
};
