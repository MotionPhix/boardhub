<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">

  <style>
    @page {
      margin: 1.5cm;
      font-family: 'Lekton', monospace;
    }
    /* Reset and base styles */
    body {
      font-family: 'Lekton', monospace;
      font-size: 12pt;
      line-height: 1.6;
      color: #1f2937;
      margin: 0;
      padding: 0;
    }

    /* Typography */
    h1, h2, h3, h4 {
      font-weight: bold;
      color: #111827;
      margin: 0;
      padding: 0;
    }

    h1 { font-size: 24pt; }
    h2 { font-size: 16pt; }
    h3 { font-size: 14pt; }
    h4 { font-size: 12pt; }

    /* Layout components */
    .page-break {
      page-break-after: always;
    }

    .cover-page {
      text-align: center;
      height: 100%;
      position: relative;
      padding: 2.5cm 0;
      page-break-after: always;
    }

    /* Header and Footer */
    .header {
      position: fixed;
      top: -2cm;
      left: 0;
      right: 0;
      height: 1.5cm;
    }

    .footer {
      position: fixed;
      bottom: -2cm;
      left: 0;
      right: 0;
      height: 1.5cm;
      text-align: center;
      font-size: 9pt;
      color: #6b7280;
      border-top: 0.5pt solid #e5e7eb;
      padding-top: 0.5cm;
    }

    /* Content sections */
    .content {
      margin-top: 1cm;
    }

    .section {
      margin: 1.5cm 0;
      page-break-inside: avoid;
    }

    .section-title {
      font-weight: bold;
      color: #111827;
      border-bottom: 0.5pt solid #e5e7eb;
      padding-bottom: 0.25cm;
      margin-bottom: 0.5cm;
    }

    /* Tables */
    table {
      width: 100%;
      border-collapse: collapse;
      margin: 1cm 0;
    }

    th {
      background-color: #f3f4f6;
      font-weight: bold;
      text-align: left;
      padding: 0.2cm 0.5cm;
      border: 0.5pt solid #d1d5db;
      color: #111827;
    }

    td {
      padding: 0.2cm 0.5cm;
      border-top: 0.5pt solid #d1d5db;
      vertical-align: top;
    }

    /* Signatures */
    .signatures {
      margin-top: 2cm;
      page-break-inside: avoid;
    }

    .signature-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2cm;
    }

    .signature-box {
      border-top: 0.5pt solid #d1d5db;
      padding-top: 1cm;
    }

    .signature-line {
      border-bottom: 0.5pt solid #000;
      margin: 1cm 0;
      width: 80%;
    }

    /* Company Details */
    .company-details {
      font-size: 9pt;
      color: #4b5563;
      margin-top: 1cm;
    }

    /* Utilities */
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .font-bold { font-weight: bold }
    .text-sm { font-size: 9pt; }
    .text-gray { color: #6b7280; }
    .mt-1 { margin-top: 0.25cm; }
    .mt-2 { margin-top: 0.5cm; }
    .mt-4 { margin-top: 1cm; }
    .mt-8 { margin-top: 2cm; }
    .mb-1 { margin-bottom: 0.25cm; }
    .mb-2 { margin-bottom: 0.5cm; }
    .mb-4 { margin-bottom: 1cm; }
    .mb-8 { margin-bottom: 2cm; }
  </style>

  @stack('styles')
</head>
<body>
@if(isset($showHeader) && $showHeader)
  <div class="header">
    @include('contracts.templates.partials.header')
  </div>
@endif

@yield('content')

<div class="footer">
  @include('contracts.templates.partials.footer')
</div>
</body>
</html>
