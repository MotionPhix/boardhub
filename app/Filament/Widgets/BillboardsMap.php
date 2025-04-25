<?php

namespace App\Filament\Widgets;

use App\Models\Billboard;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\View;

class BillboardsMap extends Widget
{
  protected static string $view = 'filament.widgets.billboards-map';

  protected int | string | array $columnSpan = 'full';

  public function getBillboardsData()
  {
    return Billboard::with(['location', 'contracts' => function ($query) {
      $query->where('agreement_status', 'active');
    }])
      ->get()
      ->map(function ($billboard) {
        $activeContract = $billboard->contracts->first();
        return [
          'id' => $billboard->id,
          'name' => $billboard->name,
          'status' => $activeContract ? 'Occupied' : 'Available',
          'lat' => $billboard->latitude,
          'lng' => $billboard->longitude,
          'location' => $billboard->location->name,
          'price' => $billboard->price,
        ];
      });
  }
}
