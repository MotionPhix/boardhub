<x-mail::message>
  # {{ $greeting }}

  {{ $message }}

  @if(isset($details))
    ## Contract Details:
    @foreach($details as $label => $value)
      - {{ $label }}: {{ $value }}
    @endforeach
  @endif

  @if(isset($actionUrl))
    <x-mail::button :url="$actionUrl" :color="$color ?? 'primary'">
      {{ $actionText ?? 'View Contract' }}
    </x-mail::button>
  @endif

  Thanks,<br>
  {{ config('app.name') }}
</x-mail::message>
