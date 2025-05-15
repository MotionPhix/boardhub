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
    .footer {
      position: fixed;
      bottom: 0;
      width: 100%;
      text-align: center;
      font-size: 12px;
      color: #666;
    }
  </style>
</head>
<body>
<div class="header">
  <img src="{{ public_path('images/logo.png') }}" class="logo">
  <h1>Contract Agreement</h1>
</div>

<div class="contract-content">
  {!! $content !!}
</div>

<div class="footer">
  {{ config('app.name') }} - {{ now()->format('Y') }}
</div>
</body>
</html>
