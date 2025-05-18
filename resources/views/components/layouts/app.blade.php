<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
  <meta charset="utf-8">

  <meta name="application-name" content="{{ config('app.name') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>{{ config('app.name', 'BoardHub') }}</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Geist+Mono:wght@100..900&display=swap" rel="stylesheet">

  <style>
    [x-cloak] {
      display: none !important;
    }

    .aspect-w-4 {
      position: relative;
      padding-bottom: 75%;
    }

    .aspect-w-4 > * {
      position: absolute;
      height: 100%;
      width: 100%;
      top: 0;
      right: 0;
      bottom: 0;
      left: 0;
    }
  </style>

  @filamentStyles
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased">
@livewire('notifications')
<x-user-id/>
{{ $slot }}

@filamentScripts
@vite('resources/js/app.js')
</body>
</html>
