<div class="space-y-4">
  @if($getRecord() && $getRecord()->preview_image)
    <div class="relative group">
      <img
        src="{{ Storage::url($getRecord()->preview_image) }}"
        alt="{{ $getRecord()->name }} Preview"
        class="w-full h-auto rounded-lg shadow-lg transition-transform duration-200 group-hover:scale-105"
      >
      <div class="absolute inset-0 bg-gray-900 bg-opacity-0 group-hover:bg-opacity-50 transition-opacity duration-200 rounded-lg flex items-center justify-center">
        <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200">
          <x-filament::button
            tag="a"
            href="{{ Storage::url($getRecord()->preview_image) }}"
            target="_blank"
            rel="noopener noreferrer"
          >
            View Full Size
          </x-filament::button>
        </div>
      </div>
    </div>
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

  @if($getRecord())
    <div class="text-sm text-gray-500">
      Last updated: {{ $getRecord()->updated_at->diffForHumans() }}
    </div>
  @endif
</div>
