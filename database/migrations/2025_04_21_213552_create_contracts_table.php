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
    Schema::create('contracts', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique()->nullable();
      $table->foreignId('client_id')->constrained()->onDelete('cascade');
      $table->string('contract_number')->unique();  // Added for better contract identification
      $table->timestamp('start_date');
      $table->timestamp('end_date');
      $table->decimal('total_amount', 10, 2);  // Changed from price to total_amount
      $table->string('agreement_status');
      $table->text('notes')->nullable();
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('contracts');
  }
};
