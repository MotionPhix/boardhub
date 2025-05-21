<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('contract_templates', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid');
      $table->string('name');
      $table->text('description')->nullable();
      $table->longText('content');
      $table->string('template_type')->default('standard');
      $table->boolean('is_default')->default(false);
      $table->boolean('is_active')->default(true);
      $table->string('preview_image')->nullable();
      $table->json('variables')->nullable();
      $table->json('settings')->nullable();
      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('contract_templates');
  }
};
