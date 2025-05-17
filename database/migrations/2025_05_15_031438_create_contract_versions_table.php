<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('contract_versions', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->primary();
      $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
      $table->integer('version');
      $table->longText('content');
      $table->json('metadata')->nullable();
      $table->timestamps();

      $table->unique(['contract_id', 'version']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('contract_versions');
  }
};
