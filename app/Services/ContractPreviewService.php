<?php

namespace App\Services;

use App\Factories\ContractPreviewDataFactory;
use App\Models\ContractTemplate;
use App\Models\Settings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Beganovich\Snappdf\Snappdf;
use Illuminate\Support\Facades\File;

class ContractPreviewService
{
  public function generatePreview(ContractTemplate $template): string
  {
    // Generate paths
    $previewPath = 'contract-previews/' . Str::slug($template->name) . '-' . $template->id . '.pdf';
    //$previewImagePath = 'public/contract-previews/' . Str::slug($template->name) . '-' . $template->id . '.png';

    // Create directories if they don't exist
    Storage::makeDirectory('contract-previews');
    Storage::makeDirectory('public/contract-previews');

    try {
      // Generate sample data using the factory
      $factory = new ContractPreviewDataFactory(
        '2025-05-22 04:17:31', // Current UTC time
        'MotionPhix'           // Current user
      );

      $sampleData = $factory->create($template->content);

      // Render template with sample data
      // $html = view($previewPath, $sampleData)->render();
      $html = view('contracts.templates.' . $template->content, $sampleData)->render();

      // Initialize Snappdf
      $snappdf = new Snappdf();

      // Generate PDF
      $pdf = $snappdf
        ->setHtml($html)
        //->setOption('format', 'A4')
        /*->setOption('margin', [
          'top' => '25mm',
          'right' => '25mm',
          'bottom' => '25mm',
          'left' => '25mm',
        ])*/
        ->generate();

      // Save PDF
      return Storage::put($previewPath, $pdf);

      /*// Generate PNG preview (first page only)
      $png = $snappdf
        ->setHtml($html)
        /*->setOption('format', 'A4')
        ->setOption('margin', [
          'top' => '25mm',
          'right' => '25mm',
          'bottom' => '25mm',
          'left' => '25mm',
        ])
        ->setOption('clip', [
          'x' => 0,
          'y' => 0,
          'width' => 800,
          'height' => 1132, // Approximate A4 ratio at 800px width
        ])
        ->generate();

      // Save PNG
      Storage::put($previewImagePath, $png);

      return Storage::url($previewImagePath);*/
    } catch (\Exception $e) {
      Log::error('Contract preview generation failed: ' . $e->getMessage(), [
        'template_id' => $template->id,
        'exception' => $e,
      ]);

      throw $e;
    }
  }
}
