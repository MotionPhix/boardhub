<div class="space-y-2 w-full">
  @php
    $notification = $getRecord();
    $data = $notification->data;
    $type = $data['type'] ?? null;
  @endphp

  <div class="font-medium text-gray-900 dark:text-white">
    {{ $data['title'] ?? 'Notification' }}
  </div>
  <div class="text-sm text-gray-500 dark:text-gray-400">
    <div class="break-words">{{ $data['message'] ?? 'No message provided' }}</div>

    @if(isset($data['status']))
      <div class="mt-1">
        <span @class([
          'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full',
          'bg-green-50 text-green-700 dark:bg-green-400/10 dark:text-green-400' => $data['status'] === 'success',
          'bg-yellow-50 text-yellow-700 dark:bg-yellow-400/10 dark:text-yellow-400' => $data['status'] === 'warning',
          'bg-red-50 text-red-700 dark:bg-red-400/10 dark:text-red-400' => $data['status'] === 'error',
          'bg-blue-50 text-blue-700 dark:bg-blue-400/10 dark:text-blue-400' => $data['status'] === 'info',
        ])>
          {{ Str::title($data['status']) }}
        </span>
      </div>
    @endif
  </div>

  {{-- Time Indicator (visible only on mobile) --}}
  <div class="text-xs text-gray-400 dark:text-gray-500">
    Received {{ $notification->created_at->diffForHumans() }}
    @if ($notification->read_at)
      · Read {{ $notification->read_at->diffForHumans() }}
    @endif
  </div>
</div>
