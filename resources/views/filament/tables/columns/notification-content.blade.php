<div
  x-data="{
        open: false,
        notification: @js($getRecord())
    }"
  class="space-y-2 w-full"
>
  @php
    $notification = $getRecord();
    $data = json_decode($notification->data, true);
    $type = $data['type'] ?? 'general';
  @endphp

  {{-- Notification Content --}}
  <div
    class="cursor-pointer"
    x-on:click="open = true"
  >
    <div class="font-medium text-gray-900 dark:text-white">
      {{ $data['title'] ?? 'Notification' }}
    </div>
    <div class="text-sm text-gray-500 dark:text-gray-400">
      <div class="break-words line-clamp-2">{{ $data['message'] ?? 'No message provided' }}</div>
    </div>

    {{-- Metadata Tags --}}
    <div class="mt-2 flex flex-wrap gap-2">
      {{-- Notification Type Badge --}}
      <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                {{ str($type)->headline() }}
            </span>

      {{-- Status Badge (if exists) --}}
      @if(isset($data['status']))
        <span @class([
                    'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full',
                    'bg-green-50 text-green-700 dark:bg-green-400/10 dark:text-green-400' => $data['status'] === 'success',
                    'bg-yellow-50 text-yellow-700 dark:bg-yellow-400/10 dark:text-yellow-400' => $data['status'] === 'warning',
                    'bg-red-50 text-red-700 dark:bg-red-400/10 dark:text-red-400' => $data['status'] === 'error',
                    'bg-blue-50 text-blue-700 dark:bg-blue-400/10 dark:text-blue-400' => $data['status'] === 'info',
                ])>
                    {{ str($data['status'])->headline() }}
                </span>
      @endif

      {{-- Additional Tags based on Type --}}
      @if(isset($data['contract_number']))
        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-primary-50 text-primary-700 dark:bg-primary-400/10 dark:text-primary-400">
                    Contract #{{ $data['contract_number'] }}
                </span>
      @endif

      @if(isset($data['billboard_code']))
        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-50 text-blue-700 dark:bg-blue-400/10 dark:text-blue-400">
                    Billboard {{ $data['billboard_code'] }}
                </span>
      @endif
    </div>
  </div>

  {{-- Time Indicator (visible only on mobile) --}}
  <div class="text-xs text-gray-400 dark:text-gray-500 sm:hidden">
    {{ $notification->created_at->diffForHumans() }}
    @if ($notification->read_at)
      · Read {{ $notification->read_at->diffForHumans() }}
    @endif
  </div>

  {{-- Notification Detail Modal --}}
  <div
    x-show="open"
    x-on:click.away="open = false"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform scale-90"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-90"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
  >
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

      <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          {{-- Modal Header --}}
          <div class="sm:flex sm:items-start">
            @if(isset($data['icon']))
              <div @class([
                                'mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10',
                                'bg-green-100' => $data['status'] === 'success',
                                'bg-yellow-100' => $data['status'] === 'warning',
                                'bg-red-100' => $data['status'] === 'error',
                                'bg-blue-100' => $data['status'] === 'info',
                                'bg-gray-100' => !isset($data['status']),
                            ])>
                <x-dynamic-component
                  :component="'heroicon-o-' . str($data['icon'])->after('heroicon-o-')"
                  @class([
                      'h-6 w-6',
                      'text-green-600' => $data['status'] === 'success',
                      'text-yellow-600' => $data['status'] === 'warning',
                      'text-red-600' => $data['status'] === 'error',
                      'text-blue-600' => $data['status'] === 'info',
                      'text-gray-600' => !isset($data['status']),
                  ])
                />
              </div>
            @endif

            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
              <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                {{ $data['title'] ?? 'Notification Details' }}
              </h3>
            </div>
          </div>

          {{-- Modal Content --}}
          <div class="mt-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">
              {{ $data['message'] ?? 'No message provided' }}
            </div>

            {{-- Additional Details based on Type --}}
            @if(isset($data['contract_id']))
              <div class="mt-4">
                <h4 class="text-sm font-medium text-gray-900 dark:text-white">Contract Details</h4>
                <dl class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                  <div class="mt-1 flex justify-between">
                    <dt class="font-medium">Contract Number:</dt>
                    <dd>{{ $data['contract_number'] }}</dd>
                  </div>
                  @if(isset($data['expiry_date']))
                    <div class="mt-1 flex justify-between">
                      <dt class="font-medium">Expiry Date:</dt>
                      <dd>{{ \Carbon\Carbon::parse($data['expiry_date'])->format('M d, Y') }}</dd>
                    </div>
                  @endif
                </dl>
              </div>
            @endif

            @if(isset($data['billboard_id']))
              <div class="mt-4">
                <h4 class="text-sm font-medium text-gray-900 dark:text-white">Billboard Details</h4>
                <dl class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                  <div class="mt-1 flex justify-between">
                    <dt class="font-medium">Billboard Code:</dt>
                    <dd>{{ $data['billboard_code'] }}</dd>
                  </div>
                  @if(isset($data['location']))
                    <div class="mt-1 flex justify-between">
                      <dt class="font-medium">Location:</dt>
                      <dd>{{ $data['location'] }}</dd>
                    </div>
                  @endif
                </dl>
              </div>
            @endif

            <div class="mt-4 text-xs text-gray-500">
              Sent {{ $notification->created_at->format('M d, Y \a\t H:i') }}
              @if ($notification->read_at)
                · Read {{ $notification->read_at->diffForHumans() }}
              @endif
            </div>
          </div>
        </div>

        {{-- Modal Footer --}}
        <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          @if(isset($data['contract_id']))
            <a href="{{ route('filament.admin.resources.contracts.view', ['record' => $data['contract_id']]) }}"
               class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm">
              View Contract
            </a>
          @endif

          @if(isset($data['billboard_id']))
            <a href="{{ route('filament.admin.resources.billboards.view', ['record' => $data['billboard_id']]) }}"
               class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm">
              View Billboard
            </a>
          @endif

          <button type="button"
                  x-on:click="open = false"
                  class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
