@if (auth()->check())
  <script>
    window.userId = {{ auth()->id() }};
  </script>
@endif
