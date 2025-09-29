<?php

namespace App\Providers;

use App\Models\Membership;
use App\Models\Tenant;
use App\Observers\MembershipObserver;
use App\Observers\TenantObserver;
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
    // Register model observers
    Tenant::observe(TenantObserver::class);
    Membership::observe(MembershipObserver::class);

    // The @money Blade directive previously called into the Settings model which may
    // attempt a database query during application boot or in test environments where
    // the `settings` table may not exist. Guard the call so views can still render
    // without the Settings model present.
    Blade::directive('money', function ($expression) {
      // Use fully-qualified names inside the returned PHP to avoid import issues
      return "<?php echo (\Illuminate\Support\Facades\Schema::hasTable('settings') && class_exists('App\\Models\\Settings')) ? app(\\App\\Models\\Settings::class)->formatMoney($expression) : number_format($expression, 2); ?>";
    });
  }
}
