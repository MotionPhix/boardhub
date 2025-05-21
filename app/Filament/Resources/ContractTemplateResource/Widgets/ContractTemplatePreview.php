<?php

namespace App\Filament\Resources\ContractTemplateResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Storage;

class ContractTemplatePreview extends Widget
{
  protected static string $view = 'filament.resources.contract-template-resource.widgets.contract-template-preview';

  public $record;

  protected int | string | array $columnSpan = 'full';

  public function mount($record): void
  {
    $this->record = $record;
  }
}
