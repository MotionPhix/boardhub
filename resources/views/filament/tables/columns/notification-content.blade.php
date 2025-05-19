<div class="space-y-2 w-full">
  @php
    $notification = $getRecord();
    $data = $notification->data;
    $type = $data['type'] ?? null;
  @endphp

  {{-- Contract Notifications --}}
  @if (in_array($type, ['contract_expiring', 'contract_renewal', 'new_contract']))
    <div class="font-medium text-gray-900 dark:text-white">
      {{ $data['title'] ?? 'Contract Notification' }}
    </div>
    <div class="text-sm text-gray-500 dark:text-gray-400">
      <div class="break-words">{{ $data['message'] }}</div>
      @if (isset($data['contract_number']))
        <div class="mt-1">
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-primary-50 text-primary-700 dark:bg-primary-400/10 dark:text-primary-400">
                        Contract #{{ $data['contract_number'] }}
                    </span>
        </div>
      @endif
    </div>

    {{-- Billboard Notifications --}}
  @elseif (in_array($type, ['billboard_maintenance', 'billboard_availability']))
    <div class="font-medium text-gray-900 dark:text-white">
      {{ $data['title'] ?? 'Billboard Update' }}
    </div>
    <div class="text-sm text-gray-500 dark:text-gray-400">
      <div class="break-words">{{ $data['message'] }}</div>
      <div class="mt-1 flex flex-wrap gap-2 items-center">
        @if (isset($data['billboard_code']))
          <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-50 text-blue-700 dark:bg-blue-400/10 dark:text-blue-400">
                        Billboard {{ $data['billboard_code'] }}
                    </span>
        @endif
        @if (isset($data['location']))
          <span class="text-xs text-gray-400 dark:text-gray-500">
                        {{ $data['location'] }}
                    </span>
        @endif
      </div>
    </div>

    {{-- Payment Notifications --}}
  @elseif (in_array($type, ['payment_due', 'payment_overdue']))
    <div class="font-medium text-gray-900 dark:text-white">
      {{ $data['title'] ?? 'Payment Notification' }}
    </div>
    <div class="text-sm text-gray-500 dark:text-gray-400">
      <div class="break-words">{{ $data['message'] }}</div>
      <div class="mt-1 flex flex-wrap gap-2 items-center">
        @if (isset($data['amount']))
          <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-50 text-green-700 dark:bg-green-400/10 dark:text-green-400">
                        {{ $data['amount'] }}
                    </span>
        @endif
        @if (isset($data['due_date']))
          <span class="text-xs text-gray-400 dark:text-gray-500">
                        Due: {{ \Carbon\Carbon::parse($data['due_date'])->format('M d, Y') }}
                    </span>
        @endif
      </div>
    </div>

    {{-- Default Notification Layout --}}
  @else
    <div class="font-medium text-gray-900 dark:text-white">
      {{ $data['title'] ?? 'Notification' }}
    </div>
    <div class="text-sm text-gray-500 dark:text-gray-400 break-words">
      {{ $data['message'] }}
    </div>
  @endif

  {{-- Time Indicator (visible only on mobile) --}}
  <div class="text-xs text-gray-400 dark:text-gray-500 sm:hidden">
    {{ $notification->created_at->diffForHumans() }}
    @if ($notification->read_at)
      · Read {{ $notification->read_at->diffForHumans() }}
    @endif
  </div>
</div>
