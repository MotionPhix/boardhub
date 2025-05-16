<?php
use App\Models\Settings;
?>
  <!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <style>
    /* Use system fonts for consistency */
    body {
      font-family: 'Lato', sans-serif;
      font-size: 12px;
      line-height: 1.7;
      color: #1F2937;
      padding: 2rem;
    }

    h1, h2, h3, h4, h5, h6 {
      font-family: 'Geist Mono', monospace;
      margin-top: 1rem;
      margin-bottom: 0.5rem;
      color: #111827;
    }

    .header {
      text-align: center;
      margin-bottom: 2rem;
      border-bottom: 1px solid #ccc;
      padding-bottom: 1rem;
    }

    .logo {
      max-width: 180px;
      margin-bottom: 1rem;
    }

    .company-info h1 {
      font-size: 1.2rem;
      font-weight: bold;
      margin-bottom: 0.3rem;
    }

    .document-title {
      text-align: center;
      font-size: 1.4rem;
      font-weight: bold;
      margin-top: 1.5rem;
      margin-bottom: 1rem;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }

    .section {
      margin-bottom: 1.5rem;
      page-break-inside: avoid;
    }

    .section-title {
      font-size: 1.1rem;
      font-weight: bold;
      color: #1F2937;
      margin-bottom: 0.5rem;
      border-bottom: 1px solid #ccc;
      padding-bottom: 0.3rem;
    }

    .section-content {
      margin-top: 0.5rem;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 0.5rem;
      margin-bottom: 1rem;
    }

    th, td {
      border: 1px solid #ccc;
      padding: 0.5rem;
      vertical-align: top;
    }

    th {
      background-color: #f3f4f6;
      font-weight: bold;
      text-transform: uppercase;
      font-size: 0.85rem;
    }

    ol.clause-list {
      list-style-type: decimal;
      padding-left: 1.5rem;
      margin-top: 1rem;
    }

    ol.clause-list li {
      margin-bottom: 0.75rem;
    }

    .signature-area {
      margin-top: 3rem;
      page-break-inside: avoid;
    }

    .signature-grid {
      display: flex;
      justify-content: space-between;
      gap: 2rem;
    }

    .signature-box {
      flex: 1;
      border: 1px solid #ccc;
      padding: 1rem;
      border-radius: 0.3rem;
      background-color: #fff;
    }

    .signature-title {
      font-weight: bold;
      font-size: 0.95rem;
      color: #1F2937;
      margin-bottom: 0.5rem;
    }

    .signature-line {
      margin-top: 2.5rem;
      border-top: 1px dashed #666;
      padding-top: 0.3rem;
      font-size: 0.85rem;
      color: #4B5563;
    }

    .footer {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      padding: 0.75rem 2rem;
      text-align: center;
      font-size: 0.75rem;
      color: #6B7280;
      border-top: 1px solid #E5E7EB;
      background-color: #F9FAFB;
    }

    .footer .page-number:after {
      content: counter(page);
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
    <div style="font-size: 11px; color: #4B5563;">
      {{ Settings::get('company_profile')['address'] }}<br>
      Tel: {{ Settings::get('company_profile')['phone'] }} | Email: {{ Settings::get('company_profile')['email'] }}
    </div>
  </div>
  <div class="document-title">Billboard Advertising Rental Agreement</div>
</div>

<!-- Meta Info -->
<div class="section">
  <p><strong>Contract Serial No:</strong> {{ $contract->contract_number }}</p>
</div>

<!-- Parties Section -->
<div class="section">
  <h2 class="section-title">Parties to the Agreement</h2>
  <div class="section-content">
    <p>This Billboard Advertising Agreement (hereinafter referred to as the "Agreement") is made on the {{ $date }} by {{ Settings::get('company_profile')['name'] }}, in the Republic of {{ Settings::get('company_profile')['address']['country'] }}, hereinafter referred to as “THE LANDLORD”.</p>

    <p>{{ $contract->client->company }}, a company incorporated under the Companies Act No. 10 of 2017 and registered as of the Laws of
      {{ $contract->client->country }}, having its registered office at {{ $contract->client->address }}, hereinafter referred to as “THE TENANT”.</p>
  </div>
</div>

<!-- Recitals -->
<div class="section">
  <h2 class="section-title">Recitals</h2>
  <div class="section-content">
    <ol class="clause-list">
      <li>{{ $contract->client->company }} is interested in renting advertising sites belonging to {{ Settings::get('company_profile')['name'] }} for advertising purposes.</li>
      <li>{{ Settings::get('company_profile')['name'] }} agrees to make available the site(s) for use as advertising space.</li>
      <li>Both parties have agreed to enter into this agreement to record, clarify, and regulate their future relationship in accordance with the terms and conditions stated hereinafter.</li>
    </ol>
  </div>
</div>

<!-- Agreement Details -->
<div class="section">
  <h2 class="section-title">Agreement Period</h2>
  <table>
    <tr>
      <th>Start Date</th>
      <td>{{ $contract->start_date->format('jS F Y') }}</td>
      <th>End Date</th>
      <td>{{ $contract->end_date->format('jS F Y') }}</td>
    </tr>
    <tr>
      <th>Payment Terms</th>
      <td colspan="3">{{ $contract->payment_terms }} days</td>
    </tr>
  </table>
</div>

<!-- Billboard Details -->
<div class="section">
  <h2 class="section-title">Billboard Locations</h2>
  <p><strong>Number of Billboards:</strong> {{ count($contract->billboards) }} Site(s)</p>
  <table>
    <thead>
    <tr>
      <th>#</th>
      <th>Location</th>
      <th>Size (W x H)</th>
      <th>Rental Rate (Excl. VAT)</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($contract->billboards as $index => $billboard)
      <tr>
        <td>{{ $index + 1 }}</td>
        <td>{{ $billboard->location->full_address }}</td>
        <td>{{ $billboard->size }}</td>
        <td>{{ number_format($billboard->pivot->rate, 2) }} {{ $contract->currency_code }}</td>
      </tr>
    @endforeach
    </tbody>
  </table>
  <p><strong>Total Monthly Rent (Excl. VAT):</strong> {{ number_format($contract->contract_total, 2) }} {{ $contract->currency_code }}</p>
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

<!-- Signatures -->
<div class="signature-area">
  <div class="signature-grid">
    <div class="signature-box">
      <div class="signature-title">For THE LANDLORD</div>
      <p><strong>Name:</strong> ___________________________</p>
      <p><strong>Signature:</strong> ________________________</p>
      <p><strong>Designation:</strong> _______________________</p>
      <p><strong>Date:</strong> ______________________________</p>
    </div>
    <div class="signature-box">
      <div class="signature-title">For THE TENANT</div>
      <p><strong>Name:</strong> ___________________________</p>
      <p><strong>Signature:</strong> ________________________</p>
      <p><strong>Designation:</strong> _______________________</p>
      <p><strong>Date:</strong> ______________________________</p>
    </div>
  </div>
</div>

<!-- Footer -->
<div class="footer">
  <div>{{ Settings::get('document_settings')['contract_footer_text'] }}</div>
  <div>{{ Settings::get('company_profile')['name'] }} © {{ now()->format('Y') }} | Page <span class="page-number"></span></div>
</div>

</body>
</html>
