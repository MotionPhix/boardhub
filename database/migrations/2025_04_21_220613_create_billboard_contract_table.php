<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('billboard_contract', function (Blueprint $table) {
      $table->id();
      $table->foreignId('billboard_id')->constrained()->onDelete('cascade');
      $table->foreignId('contract_id')->constrained()->onDelete('cascade');
      $table->decimal('price', 10, 2);  // Individual billboard price in this contract
      $table->timestamp('start_date')->nullable();  // Optional override of contract dates
      $table->timestamp('end_date')->nullable();    // Optional override of contract dates
      $table->string('status')->default('active');
      $table->text('notes')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('billboard_contract');
  }
};
