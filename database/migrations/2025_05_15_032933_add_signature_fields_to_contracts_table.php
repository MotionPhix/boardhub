<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('contracts', function (Blueprint $table) {
      $table->json('signatures')->nullable();
      $table->timestamp('signed_at')->nullable();
      $table->foreignId('contract_template_id')->nullable()->constrained();
    });
  }

  public function down(): void
  {
    Schema::table('contracts', function (Blueprint $table) {
      $table->dropColumn(['signatures', 'signed_at']);
      $table->dropForeignIdFor('contract_template_id');
    });
  }
};
