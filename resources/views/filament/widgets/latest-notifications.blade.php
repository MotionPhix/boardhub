<x-filament-widgets::widget>
  <x-filament::section>
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-bold tracking-tight">
        Latest Notifications
      </h2>
      @if($this->getNotifications()->isNotEmpty())
        <x-filament::button
          wire:click="markAllAsRead"
          size="sm"
        >
          Mark all as read
        </x-filament::button>
      @endif
    </div>

    <div class="space-y-4 mt-4">
      @forelse($this->getNotifications() as $notification)
        <div class="flex items-start justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
          <div>
            <p class="text-sm font-medium text-gray-900 dark:text-white">
              {{ $notification->data['message'] }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
              {{ $notification->created_at->diffForHumans() }}
            </p>
          </div>
          <x-filament::button
            wire:click="markAsRead('{{ $notification->id }}')"
            size="sm"
            color="gray"
          >
            Dismiss
          </x-filament::button>
        </div>
      @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">
          No new notifications
        </p>
      @endforelse
    </div>
  </x-filament::section>
</x-filament-widgets::widget>
