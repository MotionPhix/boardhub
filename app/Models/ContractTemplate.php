<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Mail;

class ContractTemplate extends Model
{
  use SoftDeletes;

  protected $fillable = [
    'name',
    'description',
    'content',
    'is_default',
    'variables',
  ];

  protected $casts = [
    'is_default' => 'boolean',
    'variables' => 'array',
  ];

  public function generatePdf(?ContractTemplate $template = null): string
  {
    // If no template provided, use default
    $template = $template ?? ContractTemplate::where('is_default', true)->first();

    if (!$template) {
      throw new \Exception('No contract template available');
    }

    $content = $template->content;

    // Replace variables
    $variables = [
      'contract_number' => $this->contract_number,
      'client_name' => $this->client->name,
      'client_company' => $this->client->company,
      'start_date' => $this->start_date->format('F j, Y'),
      'end_date' => $this->end_date->format('F j, Y'),
      'total_amount' => number_format($this->contract_final_amount, 2),
      'currency' => $this->currency_code,
      'billboards' => $this->billboards->map(function ($billboard) {
        return "- {$billboard->name} ({$billboard->location->name})";
      })->join("\n"),
    ];

    foreach ($variables as $key => $value) {
      $content = str_replace('{{'.$key.'}}', $value, $content);
    }

    // Generate PDF using Laravel PDF package
    $pdf = \PDF::loadView('contracts.template', [
      'content' => $content,
      'contract' => $this,
    ]);

    return $pdf->output();
  }

  public function emailToClient(): void
  {
    $pdf = $this->generatePdf();

    Mail::to($this->client->email)
      ->send(new \App\Mail\ContractMail($this, $pdf));
  }
}
