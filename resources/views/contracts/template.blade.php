<?php
use App\Models\Settings;
?>

<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Geist Mono', monospace;
    }

    body {
      font-size: 13px;
      line-height: 1.6;
      color: #1F2937;
      padding: 2.5rem;
    }

    /* Header Styles */
    .header {
      text-align: center;
      margin-bottom: 3rem;
      padding-bottom: 2rem;
      border-bottom: 2px solid #E5E7EB;
    }

    .logo {
      max-width: 180px;
      margin-bottom: 1.5rem;
    }

    .company-info {
      margin-bottom: 2rem;
      text-align: center;
    }

    .company-info h1 {
      color: #1F2937;
      font-size: 1.5rem;
      font-weight: bold;
      margin-bottom: 0.5rem;
    }

    .company-details {
      font-size: 0.9rem;
      color: #4B5563;
      line-height: 1.4;
    }

    /* Contract Header */
    .contract-header {
      text-align: center;
      margin-bottom: 2rem;
    }

    .contract-title {
      font-size: 1.8rem;
      font-weight: 900;
      color: #1F2937;
      margin-bottom: 1rem;
      text-transform: uppercase;
    }

    .contract-meta {
      display: flex;
      justify-content: space-between;
      margin: 1.5rem auto;
      max-width: 500px;
      padding: 1rem;
      background-color: #F9FAFB;
      border-radius: 0.5rem;
      border: 1px solid #E5E7EB;
    }

    .meta-item {
      flex: 1;
      padding: 0 1rem;
      border-right: 1px solid #E5E7EB;
    }

    .meta-item:last-child {
      border-right: none;
    }

    .meta-label {
      font-size: 0.75rem;
      text-transform: uppercase;
      color: #6B7280;
      margin-bottom: 0.25rem;
    }

    .meta-value {
      font-weight: bold;
      color: #1F2937;
    }

    /* Contract Content */
    .contract-content {
      margin: 2rem 0;
      padding: 0 1rem;
    }

    .section {
      margin-bottom: 2rem;
    }

    .section-title {
      font-size: 1.1rem;
      font-weight: bold;
      color: #1F2937;
      margin-bottom: 1rem;
      text-transform: uppercase;
    }

    .section-content {
      margin-bottom: 1.5rem;
      text-align: justify;
    }

    /* Tables */
    .table-container {
      margin: 1.5rem 0;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin: 1rem 0;
      background-color: #FFFFFF;
    }

    table th,
    table td {
      padding: 0.75rem;
      text-align: left;
      border: 1px solid #E5E7EB;
    }

    table th {
      background-color: #F9FAFB;
      font-weight: bold;
      color: #1F2937;
      text-transform: uppercase;
      font-size: 0.8rem;
    }

    table td {
      font-size: 0.9rem;
    }

    /* Financial Summary */
    .financial-summary {
      margin: 2rem 0;
      padding: 1.5rem;
      background-color: #F9FAFB;
      border-radius: 0.5rem;
      border: 1px solid #E5E7EB;
    }

    .summary-row {
      display: flex;
      justify-content: space-between;
      padding: 0.75rem 0;
      border-bottom: 1px solid #E5E7EB;
    }

    .summary-row:last-child {
      border-bottom: none;
      font-weight: bold;
      color: #1F2937;
    }

    .summary-label {
      color: #4B5563;
    }

    .summary-value {
      font-family: monospace;
    }

    /* Signature Area */
    .signature-area {
      margin-top: 4rem;
      page-break-inside: avoid;
    }

    .signature-grid {
      display: flex;
      justify-content: space-between;
      gap: 2rem;
    }

    .signature-box {
      flex: 1;
      padding: 1.5rem;
      border: 1px solid #E5E7EB;
      border-radius: 0.5rem;
      background-color: #FFFFFF;
    }

    .signature-title {
      font-weight: bold;
      color: #1F2937;
      margin-bottom: 0.5rem;
      font-size: 0.9rem;
      text-transform: uppercase;
    }

    .signature-meta {
      color: #6B7280;
      font-size: 0.85rem;
      margin-bottom: 1rem;
    }

    .signature-line {
      margin-top: 4rem;
      border-top: 1px solid #9CA3AF;
      padding-top: 0.5rem;
      color: #6B7280;
      font-size: 0.8rem;
    }

    /* Footer */
    .footer {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      padding: 1rem 2rem;
      text-align: center;
      font-size: 0.75rem;
      color: #6B7280;
      border-top: 1px solid #E5E7EB;
      background-color: #F9FAFB;
    }

    .footer .page-number:after {
      content: counter(page);
    }

    /* Specific Elements */
    .terms-list {
      list-style-type: decimal;
      padding-left: 1.5rem;
      margin: 1rem 0;
    }

    .terms-list li {
      margin-bottom: 0.75rem;
    }

    .billboard-list {
      margin: 1rem 0;
    }

    .billboard-item {
      padding: 0.75rem;
      border: 1px solid #E5E7EB;
      border-radius: 0.25rem;
      margin-bottom: 0.5rem;
    }

    @page {
      margin: 100px 25px;
      footer: page-footer;
    }
  </style>
</head>
<body>
<div class="header">
  <img src="{{ public_path('images/logo.png') }}" class="logo" alt="{{ Settings::get('company_profile')['name'] }} Logo">

  <div class="company-info">
    <h1>{{ Settings::get('company_profile')['name'] }}</h1>
    <div class="company-details">
      @if(Settings::get('company_profile')['address'])
        {{ Settings::get('company_profile')['address'] }}<br>
      @endif
      @if(Settings::get('company_profile')['phone'])
        Tel: {{ Settings::get('company_profile')['phone'] }}<br>
      @endif
      @if(Settings::get('company_profile')['email'])
        Email: {{ Settings::get('company_profile')['email'] }}<br>
      @endif
      @if(Settings::get('company_profile')['registration_number'])
        Registration No: {{ Settings::get('company_profile')['registration_number'] }}<br>
      @endif
      @if(Settings::get('company_profile')['tax_number'])
        VAT/Tax No: {{ Settings::get('company_profile')['tax_number'] }}
      @endif
    </div>
  </div>

  <div class="contract-header">
    <h1 class="contract-title">Billboard Advertising Agreement</h1>
    <div class="contract-meta">
      <div class="meta-item">
        <div class="meta-label">Contract Number</div>
        <div class="meta-value">{{ $contract->contract_number }}</div>
      </div>
      <div class="meta-item">
        <div class="meta-label">Date</div>
        <div class="meta-value">{{ $date }}</div>
      </div>
      <div class="meta-item">
        <div class="meta-label">Status</div>
        <div class="meta-value">{{ ucfirst($contract->agreement_status) }}</div>
      </div>
    </div>
  </div>
</div>

<div class="contract-content">
  <!-- Parties Section -->
  <div class="section">
    <h2 class="section-title">Parties to the Agreement</h2>
    <div class="section-content">
      <p>This Billboard Advertising Agreement (hereinafter referred to as the "Agreement") is made and entered into on {{ $date }} by and between:</p>
      <p><strong>{{ Settings::get('company_profile')['name'] }}</strong> (hereinafter referred to as the "Provider"), a company registered under the laws of Malawi with registration number {{ Settings::get('company_profile')['registration_number'] }}, having its principal place of business at {{ Settings::get('company_profile')['address'] }};</p>
      <p>AND</p>
      <p><strong>{{ $contract->client->company }}</strong> (hereinafter referred to as the "Client"), represented by {{ $contract->client->name }}, having its principal place of business at {{ $contract->client->address }}.</p>
    </div>
  </div>

  <!-- Agreement Details -->
  <div class="section">
    <h2 class="section-title">Agreement Details</h2>
    <div class="section-content">
      <div class="table-container">
        <table>
          <tr>
            <th>Description</th>
            <th>Details</th>
          </tr>
          <tr>
            <td>Agreement Period</td>
            <td>From {{ $contract->start_date->format('F j, Y') }} to {{ $contract->end_date->format('F j, Y') }}</td>
          </tr>
          <tr>
            <td>Payment Terms</td>
            <td>{{ $contract->payment_terms }} days</td>
          </tr>
          <tr>
            <td>Currency</td>
            <td>{{ $contract->currency_code }}</td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  <!-- Billboard Details -->
  <div class="section">
    <h2 class="section-title">Billboard Details</h2>
    <div class="section-content">
      <div class="billboard-list">
        @foreach($contract->billboards as $billboard)
          <div class="billboard-item">
            <strong>{{ $billboard->name }}</strong><br>
            Location: {{ $billboard->location->name }},
            {{ $billboard->location->city->name }},
            {{ $billboard->location->state->name }},
            {{ $billboard->location->country->name }}<br>
            Size: {{ $billboard->size }}<br>
            Monthly Rate: {{ number_format($billboard->pivot->rate, 2) }} {{ $contract->currency_code }}
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <!-- Financial Summary -->
  <div class="section">
    <h2 class="section-title">Financial Summary</h2>
    <div class="financial-summary">
      <div class="summary-row">
        <span class="summary-label">Contract Total</span>
        <span class="summary-value">{{ number_format($contract->contract_total, 2) }} {{ $contract->currency_code }}</span>
      </div>
      @if($contract->contract_discount > 0)
        <div class="summary-row">
          <span class="summary-label">Discount Applied</span>
          <span class="summary-value">{{ number_format($contract->contract_discount, 2) }} {{ $contract->currency_code }}</span>
        </div>
      @endif
      <div class="summary-row">
        <span class="summary-label">Final Amount</span>
        <span class="summary-value">{{ number_format($contract->contract_final_amount, 2) }} {{ $contract->currency_code }}</span>
      </div>
    </div>
  </div>

  <!-- Terms and Conditions -->
  <div class="section">
    <h2 class="section-title">Terms and Conditions</h2>
    <div class="section-content">
      {!! $content !!}
    </div>
  </div>

  <!-- Notes -->
  @if($contract->notes)
    <div class="section">
      <h2 class="section-title">Additional Notes</h2>
      <div class="section-content">
        {!! nl2br(e($contract->notes)) !!}
      </div>
    </div>
  @endif

  <!-- Signature Area -->
  <div class="signature-area">
    <div class="signature-grid">
      <div class="signature-box" style="margin-bottom: 2rem">
        <div class="signature-title">For the Client</div>
        <div class="signature-meta">
          <strong>{{ $contract->client->name }}</strong><br>
          {{ $contract->client->company }}<br>
          @if($contract->client->designation)
            {{ $contract->client->designation }}<br>
          @endif
          {{ $contract->client->email }}
        </div>
        <div class="signature-line">
          Signature & Company Stamp<br>
          Date: _______________________
        </div>
      </div>

      <div class="signature-box">
        <div class="signature-title">For {{ Settings::get('company_profile')['name'] }}</div>
        <div class="signature-meta">
          <strong>Authorized Signatory</strong><br>
          {{ Settings::get('company_profile')['name'] }}<br>
          @if(Settings::get('company_profile')['registration_number'])
            Reg. No: {{ Settings::get('company_profile')['registration_number'] }}
          @endif
        </div>
        <div class="signature-line">
          Signature & Company Stamp<br>
          Date: _______________________
        </div>
      </div>
    </div>
  </div>
</div>

<div class="footer">
  <div>{{ Settings::get('document_settings')['contract_footer_text'] }}</div>
  <div>{{ Settings::get('company_profile')['name'] }} © {{ now()->format('Y') }} | Page <span class="page-number"></span></div>
</div>
</body>
</html>
