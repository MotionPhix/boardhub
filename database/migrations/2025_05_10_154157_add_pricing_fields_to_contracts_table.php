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
      $table->decimal('base_amount', 10, 2)->default(0)->after('total_amount');
      $table->decimal('discount_amount', 10, 2)->default(0)->after('base_amount');
      $table->string('payment_terms')->nullable()->after('notes');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('contracts', function (Blueprint $table) {
      $table->dropColumn(['base_amount', 'discount_amount', 'payment_terms']);
    });
  }
};
