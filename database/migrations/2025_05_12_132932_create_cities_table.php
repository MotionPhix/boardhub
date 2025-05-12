<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('cities', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('code')->unique();
      $table->string('state')->nullable();
      $table->string('country_code');
      $table->boolean('is_active')->default(true);
      $table->text('description')->nullable();
      $table->timestamps();

      $table->foreign('country_code')
        ->references('code')
        ->on('countries')
        ->onDelete('restrict')
        ->onUpdate('cascade');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('cities');
  }
};
