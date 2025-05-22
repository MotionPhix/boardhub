<x-filament::widget>
  <x-filament::section>
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-medium">Template Preview</h2>
        @if($record->preview_image)
          <x-filament::button
            tag="a"
            href="{{ Storage::url($record->preview_image) }}"
            target="_blank"
            rel="noopener noreferrer"
            size="sm"
          >
            View Full Size
          </x-filament::button>
        @endif
      </div>

      @if($record->preview_image)
        <div class="relative group">
          <img
            src="{{ Storage::url($record->preview_image) }}"
            alt="{{ $record->name }} Preview"
            class="w-full h-auto rounded-lg shadow-lg"
          >
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
          <div>
            <h3 class="text-sm font-medium text-gray-900">Template Information</h3>
            <dl class="mt-2 text-sm text-gray-500">
              <div class="flex justify-between py-1">
                <dt>Type:</dt>
                <dd>{{ ucfirst($record->template_type) }}</dd>
              </div>
              <div class="flex justify-between py-1">
                <dt>Status:</dt>
                <dd>{{ $record->is_active ? 'Active' : 'Inactive' }}</dd>
              </div>
              <div class="flex justify-between py-1">
                <dt>Default:</dt>
                <dd>{{ $record->is_default ? 'Yes' : 'No' }}</dd>
              </div>
              <div class="flex justify-between py-1">
                <dt>Last Updated:</dt>
                <dd>{{ $record->updated_at->diffForHumans() }}</dd>
              </div>
            </dl>
          </div>

          <div>
            <h3 class="text-sm font-medium text-gray-900">Template Settings</h3>
            <dl class="mt-2 text-sm text-gray-500">
              @foreach($record->settings ?? [] as $key => $value)
                <div class="flex justify-between py-1">
                  <dt>{{ Str::title(str_replace('_', ' ', $key)) }}:</dt>
                  <dd>
                    @if(is_bool($value))
                      {{ $value ? 'Yes' : 'No' }}
                    @elseif(is_array($value))
                      {{ implode(', ', array_map(fn($item) => Str::title($item), $value)) }}
                    @else
                      {{ $value }}
                    @endif
                  </dd>
                </div>
              @endforeach
            </dl>
          </div>
        </div>

        @if($record->description)
          <div class="mt-4">
            <h3 class="text-sm font-medium text-gray-900">Description</h3>
            <p class="mt-1 text-sm text-gray-500">
              {{ $record->description }}
            </p>
          </div>
        @endif

        @if($record->variables)
          <div class="mt-4">
            <h3 class="text-sm font-medium text-gray-900">Available Variables</h3>
            <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4">
              @foreach($record->variables as $variable)
                <div class="text-sm">
                  <span class="font-mono text-gray-900">{{ $variable['name'] }}</span>
                  <p class="text-gray-500">{{ $variable['description'] }}</p>
                </div>
              @endforeach
            </div>
          </div>
        @endif
      @else
        <div class="flex items-center justify-center h-64 bg-gray-100 rounded-lg">
          <div class="text-center">
            <x-heroicon-o-document class="w-16 h-16 mx-auto text-gray-400"/>
            <span class="mt-2 block text-sm text-gray-500">
                            No preview available
                        </span>
          </div>
        </div>
      @endif
    </div>
  </x-filament::section>
</x-filament::widget>
