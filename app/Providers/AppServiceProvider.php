<?php

namespace App\Providers;

use App\Services\TenantSessionService;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    $this->app->singleton(TenantSessionService::class);
    $this->app->singleton(SubscriptionService::class);
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    Blade::directive('money', function ($expression) {
      return "<?php echo app(\App\Models\Settings::class)->formatMoney($expression); ?>";
    });
  }
}
