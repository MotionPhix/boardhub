<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    // First, let's add parent_contract_id that was missing for renewals
    Schema::table('contracts', function (Blueprint $table) {
      // Add parent_contract_id for contract renewals
      /*$table->foreignId('parent_contract_id')
        ->nullable()
        ->constrained('contracts')
        ->nullOnDelete();*/

      // Add fields for notifications
      /*$table->timestamp('last_notification_sent_at')->nullable();*/
      /*$table->integer('notification_count')->default(0);*/

      // Add new pricing fields
      $table->decimal('base_amount', 10, 2)->default(0);
      $table->decimal('discount_amount', 10, 2)->default(0);

      // Add payment terms
      $table->string('payment_terms')->nullable();

      // Add indexes for improved performance
      $table->index('agreement_status');
      $table->index(['agreement_status', 'end_date']);
      $table->index('parent_contract_id');
    });

    // Modify the billboard_contract pivot table to improve pricing tracking
    Schema::table('billboard_contract', function (Blueprint $table) {
      // Add a base_price column to track original price at time of contract
      $table->decimal('base_price', 10, 2)->after('contract_id');
      $table->decimal('discount_amount', 10, 2)->default(0)->after('base_price');
      // Rename price to final_price for clarity
      $table->renameColumn('price', 'final_price');
    });
  }

  public function down(): void
  {
    Schema::table('billboard_contract', function (Blueprint $table) {
      $table->renameColumn('final_price', 'price');
      $table->dropColumn(['base_price', 'discount_amount']);
    });

    Schema::table('contracts', function (Blueprint $table) {
      $table->dropIndex(['agreement_status']);
      $table->dropIndex(['agreement_status', 'end_date']);
      $table->dropIndex(['parent_contract_id']);

      $table->dropColumn([
        'parent_contract_id',
        'last_notification_sent_at',
        'notification_count',
        'base_amount',
        'discount_amount',
        'payment_terms'
      ]);
    });
  }
};
