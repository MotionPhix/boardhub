<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class LatestNotifications extends Widget
{
  protected static string $view = 'filament.widgets.latest-notifications';

  protected int | string | array $columnSpan = 'full';

  public function getNotifications()
  {
    return Auth::user()
      ->notifications()
      ->where('read_at', null)
      ->latest()
      ->take(5)
      ->get();
  }
}
