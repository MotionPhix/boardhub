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
      $table->string('code')->unique();
      $table->string('name');
      $table->string('state_code');
      $table->string('country_code');
      $table->boolean('is_active')->default(true);
      $table->timestamps();

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
    Schema::dropIfExists('cities');
  }
};
