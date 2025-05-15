<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
  <style>
    @font-face {
      font-family: 'Lato';
      src: url({{ storage_path('fonts/Lato-Regular.ttf') }}) format("truetype");
      font-weight: normal;
      font-style: normal;
    }
    @font-face {
      font-family: 'Lato';
      src: url({{ storage_path('fonts/Lato-Bold.ttf') }}) format("truetype");
      font-weight: bold;
      font-style: normal;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Lato', sans-serif;
    }

    body {
      font-size: 14px;
      line-height: 1.6;
      color: #374151;
      padding: 2rem;
    }

    .header {
      text-align: center;
      margin-bottom: 3rem;
      padding-bottom: 2rem;
      border-bottom: 2px solid #E5E7EB;
    }

    .logo {
      max-width: 200px;
      margin-bottom: 1.5rem;
    }

    .document-title {
      font-size: 2rem;
      font-weight: 900;
      color: #1F2937;
      margin-bottom: 0.5rem;
    }

    .document-meta {
      color: #6B7280;
      font-size: 0.875rem;
    }

    .contract-content {
      margin: 2rem 0;
      padding: 0 1rem;
    }

    .contract-content h2 {
      color: #1F2937;
      font-size: 1.5rem;
      font-weight: bold;
      margin: 2rem 0 1rem;
    }

    .contract-content h3 {
      color: #374151;
      font-size: 1.25rem;
      font-weight: bold;
      margin: 1.5rem 0 1rem;
    }

    .contract-content p {
      margin-bottom: 1rem;
      line-height: 1.8;
    }

    .contract-content ul {
      margin: 1rem 0;
      padding-left: 2rem;
    }

    .contract-content li {
      margin-bottom: 0.5rem;
    }

    .signature-area {
      margin-top: 4rem;
      page-break-inside: avoid;
      display: flex;
      justify-content: space-between;
      gap: 2rem;
    }

    .signature-box {
      flex: 1;
    }

    .signature-title {
      font-weight: bold;
      color: #1F2937;
      margin-bottom: 0.5rem;
    }

    .signature-meta {
      color: #6B7280;
      font-size: 0.875rem;
      margin-bottom: 1rem;
    }

    .signature-line {
      margin-top: 4rem;
      border-top: 1px solid #9CA3AF;
      padding-top: 0.5rem;
    }

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
    }

    .footer .page-number:after {
      content: counter(page);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin: 1rem 0;
    }

    table th,
    table td {
      padding: 0.75rem;
      text-align: left;
      border-bottom: 1px solid #E5E7EB;
    }

    table th {
      background-color: #F9FAFB;
      font-weight: bold;
      color: #1F2937;
    }

    .amount-summary {
      margin: 2rem 0;
      padding: 1rem;
      background-color: #F9FAFB;
      border-radius: 0.5rem;
    }

    .amount-row {
      display: flex;
      justify-content: space-between;
      padding: 0.5rem 0;
      border-bottom: 1px solid #E5E7EB;
    }

    .amount-row:last-child {
      border-bottom: none;
      font-weight: bold;
    }

    @page {
      margin: 100px 25px;
      footer: page-footer;
    }
  </style>
</head>
<body>
<div class="header">
  <img src="{{ public_path('images/logo.png') }}" class="logo" alt="Company Logo">
  <h1 class="document-title">Contract Agreement</h1>
  <div class="document-meta">
    <div>Contract #: {{ $contract->contract_number }}</div>
    <div>Date: {{ $date }}</div>
  </div>
</div>

<div class="contract-content">
  {!! $content !!}
</div>

<div class="signature-area">
  <div class="signature-box">
    <div class="signature-title">Client</div>
    <div class="signature-meta">
      <div>{{ $contract->client->name }}</div>
      <div>{{ $contract->client->company }}</div>
    </div>
    <div class="signature-line">
      <div>Signature & Date</div>
    </div>
  </div>

  <div class="signature-box">
    <div class="signature-title">For {{ config('app.name') }}</div>
    <div class="signature-meta">
      <div>Authorized Signatory</div>
    </div>
    <div class="signature-line">
      <div>Signature & Date</div>
    </div>
  </div>
</div>

<div class="footer">
  <div>{{ config('app.name') }} © {{ now()->format('Y') }} - All rights reserved</div>
  <div>Page <span class="page-number"></span></div>
</div>
</body>
</html>
