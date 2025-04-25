<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    $tableNames = config('permission.table_names');

    if (empty($tableNames)) {
      throw new \Exception('Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
    }

    Schema::table($tableNames['permissions'], function (Blueprint $table) {
      $table->string('description')->nullable()->after('guard_name');
    });

    Schema::table($tableNames['roles'], function (Blueprint $table) {
      $table->string('description')->nullable()->after('guard_name');
    });
  }

  public function down(): void
  {
    $tableNames = config('permission.table_names');

    if (empty($tableNames)) {
      throw new \Exception('Error: config/permission.php not found and defaults could not be merged. Please publish the package configuration before proceeding, or drop the tables manually.');
    }

    Schema::table($tableNames['permissions'], function (Blueprint $table) {
      $table->dropColumn('description');
    });

    Schema::table($tableNames['roles'], function (Blueprint $table) {
      $table->dropColumn('description');
    });
  }
};
