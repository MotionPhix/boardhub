<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <style>
    body {
      font-family: 'DejaVu Sans', sans-serif;
      font-size: 14px;
      line-height: 1.6;
    }
    .header {
      text-align: center;
      margin-bottom: 30px;
    }
    .logo {
      max-width: 200px;
      margin-bottom: 20px;
    }
    .contract-content {
      margin: 20px 0;
    }
    .signature-area {
      margin-top: 50px;
      page-break-inside: avoid;
    }
    .signature-box {
      width: 45%;
      float: left;
      margin: 20px;
    }
    .signature-line {
      border-top: 1px solid #000;
      margin-top: 50px;
      padding-top: 5px;
    }
    .footer {
      position: fixed;
      bottom: 0;
      width: 100%;
      text-align: center;
      font-size: 12px;
      color: #666;
    }
    .page-break {
      page-break-before: always;
    }
  </style>
</head>
<body>
<div class="header">
  <img src="{{ public_path('images/logo.png') }}" class="logo">

  <h1>Contract Agreement</h1>

  <p>Contract Number: {{ $contract->contract_number }}</p>

  <p>Date: {{ $date }}</p>
</div>

<div class="contract-content">
  {!! $content !!}
</div>

<div class="signature-area">
  <div class="signature-box">
    <p><strong>Client:</strong></p>
    <p>{{ $contract->client->name }}</p>
    <p>{{ $contract->client->company }}</p>
    <div class="signature-line">
      <p>Signature & Date</p>
    </div>
  </div>

  <div class="signature-box">
    <p><strong>For {{ config('app.name') }}:</strong></p>
    <p>____________________</p>
    <div class="signature-line">
      <p>Signature & Date</p>
    </div>
  </div>
</div>

<div class="footer">
  {{ config('app.name') }} - {{ now()->format('Y') }}
  <br>
  Page {PAGENO} of {nb}
</div>
</body>
</html>
