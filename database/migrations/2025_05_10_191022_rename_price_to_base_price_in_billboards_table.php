<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('billboards', function (Blueprint $table) {
      $table->renameColumn('price', 'base_price');
    });
  }

  public function down(): void
  {
    Schema::table('billboards', function (Blueprint $table) {
      $table->renameColumn('base_price', 'price');
    });
  }
};
