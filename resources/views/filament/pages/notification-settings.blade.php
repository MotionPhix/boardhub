<x-filament-panels::page>
  <div class="space-y-6">
    {{-- Introduction section --}}
    <div class="prose dark:prose-invert max-w-none">
      <p class="text-gray-500 dark:text-gray-400">
        Customize how and when you receive notifications. Your preferences are automatically saved when you toggle any setting.
      </p>
    </div>

    {{-- Settings form --}}
    <form wire:submit="submit">
      {{ $this->form }}

      <div class="mt-6 flex items-center justify-between">
        <x-filament::button
          type="submit"
          size="lg">
          Save preferences
        </x-filament::button>

        <x-filament::button
          type="button"
          color="gray"
          tag="a"
          href="{{ route('filament.admin.pages.notifications') }}"
          icon="heroicon-m-bell">
          View my notifications
        </x-filament::button>
      </div>
    </form>

    {{-- Help section --}}
    <div class="mt-8 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
      <h3 class="text-base font-medium text-gray-900 dark:text-gray-100">
        About Notification Channels
      </h3>
      <div class="mt-2 space-y-4">
        <div class="flex items-start gap-x-3">
          <div class="flex-shrink-0">
            <x-filament::icon
              icon="heroicon-m-envelope"
              class="h-5 w-5 text-primary-500"/>
          </div>
          <div class="text-sm text-gray-500 dark:text-gray-400">
            <strong class="font-medium text-gray-900 dark:text-gray-100">Email:</strong>
            Receive notifications directly in your email inbox
          </div>
        </div>
        <div class="flex items-start gap-x-3">
          <div class="flex-shrink-0">
            <x-filament::icon
              icon="heroicon-m-bell"
              class="h-5 w-5 text-primary-500"/>
          </div>
          <div class="text-sm text-gray-500 dark:text-gray-400">
            <strong class="font-medium text-gray-900 dark:text-gray-100">In-App:</strong>
            See notifications in your notification center
          </div>
        </div>
        <div class="flex items-start gap-x-3">
          <div class="flex-shrink-0">
            <x-filament::icon
              icon="heroicon-m-signal"
              class="h-5 w-5 text-primary-500"/>
          </div>
          <div class="text-sm text-gray-500 dark:text-gray-400">
            <strong class="font-medium text-gray-900 dark:text-gray-100">Push:</strong>
            Get instant notifications in real-time
          </div>
        </div>
      </div>
    </div>
  </div>
</x-filament-panels::page>
