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
    Schema::create('user_login_activities', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->string('ip_address', 45)->nullable();
      $table->string('user_agent')->nullable();
      $table->string('location')->nullable();
      $table->timestamp('login_at');
      $table->timestamp('logout_at')->nullable();
      $table->boolean('login_successful')->default(false);
      $table->string('login_type')->default('form'); // form, oauth, etc.
      $table->text('details')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('user_login_activities');
  }
};
