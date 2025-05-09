<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('contract_user', function (Blueprint $table) {
      $table->id();
      $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->string('role')->default('viewer');
      $table->timestamps();

      $table->unique(['contract_id', 'user_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('contract_user');
  }
};
