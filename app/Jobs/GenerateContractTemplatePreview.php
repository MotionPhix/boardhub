<?php

namespace App\Jobs;

use App\Models\ContractTemplate;
use App\Services\ContractPreviewService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateContractTemplatePreview implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public function __construct(
    protected ContractTemplate $template
  ) {}

  public function handle(ContractPreviewService $previewService): void
  {
    $previewUrl = $previewService->generatePreview($this->template);

    $this->template->update([
      'preview_image' => str_replace('/storage/', '', $previewUrl)
    ]);
  }
}
