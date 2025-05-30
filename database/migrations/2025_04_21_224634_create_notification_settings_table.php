<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('notification_settings', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->string('type');
      $table->string('channel');
      $table->boolean('is_enabled')->default(true);
      $table->timestamps();

      $table->unique(['user_id', 'type', 'channel']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('notification_settings');
  }
};
