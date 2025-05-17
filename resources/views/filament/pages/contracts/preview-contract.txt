<x-filament-panels::page>
  <div class="prose max-w-none">
    @if($this->record->hasMedia('contract_documents'))
      <div class="mb-4">
        <iframe
          src="{{ $this->record->getFirstMediaUrl('contract_documents') }}"
          class="w-full rounded-lg border shadow-sm"
          style="min-height: 800px;"
        >
        </iframe>
      </div>
    @else
      <div class="text-center py-12">
        <div class="max-w-xl mx-auto">
          <h2 class="text-xl font-semibold text-gray-900 mb-2">
            No contract document available
          </h2>
          <p class="text-gray-500 mb-6">
            Generate a PDF version of this contract to preview and share with the client.
          </p>
          <x-filament::button
            wire:click="generatePdf"
            type="button"
            color="primary"
            size="lg"
          >
            <x-filament::icon
              name="heroicon-m-document"
              class="h-5 w-5 mr-2"
            />
            Generate Contract
          </x-filament::button>
        </div>
      </div>
    @endif
  </div>
</x-filament-panels::page>
