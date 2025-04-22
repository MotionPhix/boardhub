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
      $table->foreignId('location_id')->constrained()->onDelete('cascade');
      $table->string('size');
      $table->string('type');
      $table->decimal('price', 10, 2);
      $table->string('physical_status');
      $table->text('description')->nullable();
      $table->decimal('latitude', 10, 8)->nullable();
      $table->decimal('longitude', 10, 8)->nullable();
      $table->timestamps();
      $table->softDeletes();
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
