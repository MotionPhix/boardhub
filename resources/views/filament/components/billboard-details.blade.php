<div class="space-y-1">
  <div class="text-sm font-medium">{{ $billboard->size }}</div>

  <div class="text-sm text-gray-500">
    {{ $billboard->name }}, {{ $billboard->location->name }}
  </div>

  @php
    $locationParts = array_filter([
      $billboard->location->city?->name,
      $billboard->location->state?->name,
      $billboard->location->country?->name
    ]);
  @endphp

  @if(count($locationParts) > 0)
    <div class="text-xs text-gray-400">
      {{ implode(' · ', $locationParts) }}
    </div>
  @endif
</div>
