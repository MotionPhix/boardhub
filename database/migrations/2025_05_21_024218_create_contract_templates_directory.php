<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
  public function up(): void
  {
    $paths = [
      resource_path('views/contracts/templates'),
      resource_path('views/contracts/templates/standard'),
      resource_path('views/contracts/templates/premium'),
      resource_path('views/contracts/templates/layouts'),
      resource_path('views/contracts/templates/partials'),
    ];

    foreach ($paths as $path) {
      if (!File::exists($path)) {
        File::makeDirectory($path, 0755, true);
      }
    }
  }

  public function down(): void
  {
    File::deleteDirectory(resource_path('views/contracts/templates'));
  }
};
