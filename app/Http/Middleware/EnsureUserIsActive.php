<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Filament\Notifications\Notification;

class EnsureUserIsActive
{
  public function handle(Request $request, Closure $next)
  {
    if (auth()->check() && !auth()->user()->is_active) {
      auth()->logout();

      Notification::make()
        ->title('Account Deactivated')
        ->body('Your account has been deactivated. Please contact your manager.')
        ->danger()
        ->send();

      return redirect()->route('filament.admin.auth.login');
    }

    return $next($request);
  }
}
