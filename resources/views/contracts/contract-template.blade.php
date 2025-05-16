<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <style>
    @page {
      margin: 2.5cm 2cm;
      font-family: 'DejaVu Sans', sans-serif;
    }

    body {
      font-size: 12pt;
      line-height: 1.6;
      color: #1f2937;
    }

    .header {
      position: fixed;
      top: -2cm;
      left: 0;
      right: 0;
      text-align: center;
    }

    .logo {
      max-width: 200px;
      margin-bottom: 20px;
    }

    .company-info {
      text-align: left;
      margin-bottom: 40px;
    }

    .contract-number {
      font-size: 16pt;
      font-weight: bold;
      margin: 20px 0;
      color: #4b5563;
    }

    .section {
      margin: 20px 0;
    }

    .section-title {
      font-weight: bold;
      font-size: 14pt;
      margin: 15px 0;
      color: #374151;
    }

    .grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }

    .signature-section {
      margin-top: 60px;
      page-break-inside: avoid;
    }

    .signature-box {
      margin: 20px 0;
      border-top: 1px solid #9ca3af;
      padding-top: 10px;
    }

    .footer {
      position: fixed;
      bottom: -2cm;
      left: 0;
      right: 0;
      text-align: center;
      font-size: 10pt;
      color: #6b7280;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
    }

    th, td {
      padding: 10px;
      border: 1px solid #d1d5db;
      text-align: left;
    }

    th {
      background-color: #f3f4f6;
      font-weight: bold;
    }

    .amount {
      text-align: right;
    }

    .meta-info {
      font-size: 9pt;
      color: #6b7280;
      text-align: right;
      margin-top: 5px;
    }
  </style>
</head>
<body>
<div class="header">
  @if($settings->getFirstMedia('logo'))
    <img src="{{ $settings->getFirstMedia('logo')->getPath() }}" class="logo" alt="{{ $settings->get('company_profile.name') }}">
  @endif
</div>

<div class="company-info">
  <h1>{{ $settings->get('company_profile.name') }}</h1>
  <p>
    {{ $settings->get('company_profile.address.street') }}<br>
    {{ $settings->get('company_profile.address.city') }}, {{ $settings->get('company_profile.address.state') }}<br>
    {{ $settings->get('company_profile.address.country') }}<br>
    @if($settings->get('company_profile.phone'))
      Phone: {{ $settings->get('company_profile.phone') }}<br>
    @endif
    Email: {{ $settings->get('company_profile.email') }}<br>
    @if($settings->get('company_profile.registration_number'))
      Reg. No: {{ $settings->get('company_profile.registration_number') }}<br>
    @endif
    @if($settings->get('company_profile.tax_number'))
      Tax No: {{ $settings->get('company_profile.tax_number') }}
    @endif
  </p>
</div>

<div class="contract-number">
  Contract Agreement #{{ $contract->contract_number }}
</div>

<div class="section">
  <div class="grid">
    <div>
      <strong>Client Information:</strong><br>
      {{ $contract->client->name }}<br>
      @if($contract->client->company)
        {{ $contract->client->company }}<br>
      @endif
      {{ $contract->client->street }}<br>
      {{ $contract->client->city }}, {{ $contract->client->state }}<br>
      {{ $contract->client->country }}<br>
      @if($contract->client->phone)
        Phone: {{ $contract->client->phone }}<br>
      @endif
      Email: {{ $contract->client->email }}
    </div>
    <div>
      <strong>Contract Details:</strong><br>
      Start Date: {{ $contract->start_date->format($settings->get('localization.date_format')) }}<br>
      End Date: {{ $contract->end_date->format($settings->get('localization.date_format')) }}<br>
      Duration: {{ $contract->start_date->diffInMonths($contract->end_date) }} months<br>
      Status: {{ ucfirst($contract->agreement_status) }}
    </div>
  </div>
</div>

<div class="section">
  <h2 class="section-title">Billboard Details</h2>
  <table>
    <thead>
    <tr>
      <th>Billboard</th>
      <th>Location</th>
      <th>Dimensions</th>
      <th class="amount">Base Price</th>
    </tr>
    </thead>
    <tbody>
    @foreach($contract->billboards as $billboard)
      <tr>
        <td>{{ $billboard->name }}</td>
        <td>{{ $billboard->location->name }}</td>
        <td>{{ $billboard->dimensions }}</td>
        <td class="amount">
          {{ $settings->get('currency_settings')[$contract->currency_code]['symbol'] }}
          {{ number_format($billboard->pivot->billboard_base_price, 2) }}
        </td>
      </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr>
      <td colspan="3" class="amount"><strong>Total Amount:</strong></td>
      <td class="amount">
        <strong>
          {{ $settings->get('currency_settings')[$contract->currency_code]['symbol'] }}
          {{ number_format($contract->contract_total, 2) }}
        </strong>
      </td>
    </tr>
    @if($contract->contract_discount > 0)
      <tr>
        <td colspan="3" class="amount">Discount:</td>
        <td class="amount">
          {{ $settings->get('currency_settings')[$contract->currency_code]['symbol'] }}
          {{ number_format($contract->contract_discount, 2) }}
        </td>
      </tr>
      <tr>
        <td colspan="3" class="amount"><strong>Final Amount:</strong></td>
        <td class="amount">
          <strong>
            {{ $settings->get('currency_settings')[$contract->currency_code]['symbol'] }}
            {{ number_format($contract->contract_final_amount, 2) }}
          </strong>
        </td>
      </tr>
    @endif
    </tfoot>
  </table>
</div>

<div class="section">
  <h2 class="section-title">Terms and Conditions</h2>
  {!! $settings->get('document_settings.default_contract_terms') !!}
</div>

<div class="signature-section">
  <div class="grid">
    <div class="signature-box">
      <p><strong>For {{ $settings->get('company_profile.name') }}:</strong></p>
      @if(isset($contract->signatures['company']))
        <img src="{{ $contract->signatures['company'] }}" alt="Company Signature" style="max-height: 100px"><br>
      @else
        <br><br><br>
      @endif
      Name: ____________________<br>
      Title: ____________________<br>
      Date: ____________________
    </div>
    <div class="signature-box">
      <p><strong>For {{ $contract->client->company ?: $contract->client->name }}:</strong></p>
      @if(isset($contract->signatures['client']))
        <img src="{{ $contract->signatures['client'] }}" alt="Client Signature" style="max-height: 100px"><br>
      @else
        <br><br><br>
      @endif
      Name: ____________________<br>
      Title: ____________________<br>
      Date: ____________________
    </div>
  </div>
</div>

<div class="footer">
  {!! $settings->get('document_settings.contract_footer_text') !!}
  <div class="meta-info">
    Generated on {{ now()->setTimezone($settings->get('localization.timezone'))->format($settings->get('localization.date_format') . ' ' . $settings->get('localization.time_format')) }} by {{ $generatedBy ?? 'System' }}
  </div>
</div>
</body>
</html>
