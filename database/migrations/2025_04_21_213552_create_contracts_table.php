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
      $table->foreignId('billboard_id')->constrained()->onDelete('cascade');
      $table->string('client_name');
      $table->string('client_contact');
      $table->string('client_email');
      $table->date('start_date');
      $table->date('end_date');
      $table->decimal('contract_value', 10, 2);
      $table->enum('status', ['Active', 'Pending', 'Expired', 'Cancelled']);
      $table->text('notes')->nullable();
      $table->timestamps();
      $table->softDeletes();
      $table->timestamps();
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
