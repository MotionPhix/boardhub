<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('contracts', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique()->nullable();

      // Basic contract information
      $table->foreignId('client_id')->constrained()->onDelete('cascade');
      $table->foreignId('parent_contract_id')->nullable()->constrained('contracts')->nullOnDelete();
      $table->string('contract_number')->unique();

      // Dates
      $table->timestamp('start_date');
      $table->timestamp('end_date');

      // Financial information
      $table->decimal('base_price', 10, 2)->default(0);
      $table->decimal('discount_amount', 10, 2)->default(0);
      $table->decimal('total_amount', 10, 2);
      $table->string('currency_code')->default('MWK');

      // Status and notes
      $table->text('notes')->nullable();
      $table->enum('agreement_status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');

      // Notification tracking
      $table->timestamp('last_notification_sent_at')->nullable();
      $table->unsignedInteger('notification_count')->default(0);

      // Standard timestamps
      $table->timestamps();
      $table->softDeletes();

      // Indexes for better performance
      $table->index('agreement_status');
      $table->index(['agreement_status', 'end_date']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('contracts');
  }
};
