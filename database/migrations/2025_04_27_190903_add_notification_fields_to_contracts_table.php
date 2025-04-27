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
    Schema::table('contracts', function (Blueprint $table) {
      $table->timestamp('last_notification_sent_at')->nullable();
      $table->unsignedInteger('notification_count')->default(0);
      $table->foreignId('parent_contract_id')
        ->nullable()
        ->constrained('contracts')
        ->nullOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('contracts', function (Blueprint $table) {
      $table->dropForeign(['parent_contract_id']);
      $table->dropColumn(['last_notification_sent_at', 'notification_count', 'parent_contract_id']);
    });
  }
};
