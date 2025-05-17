<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('settings', function (Blueprint $table) {
      $table->id();

      // Instead of a generic key-value store, we'll use specific columns
      // Company Profile
      $table->string('company_name')->nullable();
      $table->string('company_email')->nullable();
      $table->string('company_phone')->nullable();
      $table->string('company_address_street')->nullable();
      $table->string('company_address_city')->nullable();
      $table->string('company_address_state')->nullable();
      $table->string('company_address_country')->nullable();
      $table->string('company_registration_number')->nullable();
      $table->string('company_tax_number')->nullable();

      // Localization
      $table->string('timezone')->default('Africa/Blantyre');
      $table->string('locale')->default('en');
      $table->string('date_format')->default('d M, Y');
      $table->string('time_format')->default('H:i:s');

      // Document Settings
      $table->text('default_contract_terms')->nullable();
      $table->text('contract_footer_text')->nullable();

      // Billboard Settings
      $table->string('billboard_code_prefix')->default('BH');
      $table->string('billboard_code_separator')->default('-');
      $table->integer('billboard_code_counter_length')->default(5);

      $table->timestamps();
    });

    // Create a separate table for currencies
    Schema::create('currencies', function (Blueprint $table) {
      $table->id();
      $table->string('code', 3)->unique();
      $table->string('symbol', 5);
      $table->string('name');
      $table->boolean('is_default')->default(false);
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('settings');
    Schema::dropIfExists('currencies');
  }
};
