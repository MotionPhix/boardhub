<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('locations', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();
      $table->string('name');
      $table->string('description')->nullable();
      $table->string('city_code');
      $table->string('state_code');
      $table->string('country_code');
      $table->boolean('is_active')->default(true);
      $table->timestamps();

      // Add foreign key constraints
      $table->foreign('city_code')
        ->references('code')
        ->on('cities')
        ->onDelete('restrict')
        ->onUpdate('cascade');

      $table->foreign('state_code')
        ->references('code')
        ->on('states')
        ->onDelete('restrict')
        ->onUpdate('cascade');

      $table->foreign('country_code')
        ->references('code')
        ->on('countries')
        ->onDelete('restrict')
        ->onUpdate('cascade');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('locations');
  }
};
