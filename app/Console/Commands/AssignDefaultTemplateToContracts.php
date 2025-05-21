<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ContractTemplate;
use Illuminate\Console\Command;

class AssignDefaultTemplateToContracts extends Command
{
  protected $signature = 'contracts:assign-template';

  protected $description = 'Assigns the default template to existing contracts';

  public function handle()
  {
    $defaultTemplate = ContractTemplate::where('is_default', true)->first();

    if (!$defaultTemplate) {
      $this->error('No default template found!');
      return 1;
    }

    $count = Contract::whereNull('template_id')->update([
      'template_id' => $defaultTemplate->id
    ]);

    $this->info("Updated {$count} contracts with the default template.");

    return 0;
  }
}
