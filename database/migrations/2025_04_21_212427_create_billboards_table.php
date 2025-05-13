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
      $table->uuid('uuid')->unique();
      $table->string('name');
      $table->string('code')->unique()->nullable();
      $table->foreignId('location_id')->constrained()->onDelete('cascade');
      $table->string('size');
      $table->decimal('base_price', 10, 2);
      $table->string('currency_code')->default('MWK');
      $table->string('physical_status');

      $table->text('description')->nullable();
      $table->decimal('latitude', 20, 8)->nullable();
      $table->decimal('longitude', 20, 8)->nullable();
      $table->string('site')->nullable();

      $table->boolean('is_active')->default(true);
      $table->json('meta_data')->nullable();

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
