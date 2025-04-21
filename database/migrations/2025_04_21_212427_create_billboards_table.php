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
    Schema::create('billboards', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique()->nullable();
      $table->string('name');
      $table->string('location');
      $table->decimal('price', 10, 2);
      $table->string('size');
      $table->enum('type', ['Static', 'Digital', 'Mobile']);
      $table->enum('status', ['Available', 'Occupied', 'Maintenance']);
      $table->date('contract_start_date')->nullable();
      $table->date('contract_end_date')->nullable();
      $table->text('description')->nullable();
      $table->json('photos')->nullable();
      $table->json('specifications')->nullable();
      $table->decimal('latitude', 10, 8)->nullable();
      $table->decimal('longitude', 10, 8)->nullable();
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
    Schema::dropIfExists('billboards');
  }
};
