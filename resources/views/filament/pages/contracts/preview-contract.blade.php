<x-filament-panels::page>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div class="flex items-center space-x-4">
        <x-filament::input.wrapper>
          <x-filament::input.select
            wire:model.live="previewMode"
            :options="['web' => 'Web View', 'pdf' => 'PDF Preview']"
          />
        </x-filament::input.wrapper>

        @if($versions->isNotEmpty())
          <x-filament::input.wrapper>
            <x-filament::input.select
              wire:model.live="selectedVersion"
              :options="$versions->mapWithKeys(fn ($version, $index) => [
                                $version->id => 'Version ' . ($versions->count() - $index) . ' - ' . $version->created_at->format('M j, Y H:i')
                            ])"
            />
          </x-filament::input.wrapper>
        @endif
      </div>

      <div>
        @if($record->signed_at)
          <x-filament::badge color="success">
            Signed on {{ $record->signed_at->format('M j, Y') }}
          </x-filament::badge>
        @else
          <x-filament::badge color="warning">
            Unsigned
          </x-filament::badge>
        @endif
      </div>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
      @if($previewMode === 'web')
        <div class="p-6">
          {!! $currentVersion?->content !!}
        </div>
      @else
        @if($record->hasMedia('contract_documents'))
          <iframe
            src="{{ $record->getFirstMediaUrl('contract_documents') }}"
            class="w-full"
            style="height: 800px;"
          ></iframe>
        @else
          <div class="p-6 text-center text-gray-500">
            No PDF document available. Please generate one first.
          </div>
        @endif
      @endif
    </div>

    @if($record->signatures)
      <div class="mt-4 space-y-4">
        <h3 class="text-lg font-medium">Signatures</h3>
        <div class="grid grid-cols-2 gap-4">
          @foreach($record->signatures as $type => $signature)
            <div class="bg-white rounded-lg p-4">
              <h4 class="text-sm font-medium text-gray-500">{{ ucfirst($type) }} Signature</h4>
              <img src="{{ $signature }}" alt="{{ $type }} signature" class="mt-2 max-h-20">
            </div>
          @endforeach
        </div>
      </div>
    @endif
  </div>

  <x-filament::modal id="signatureModal" width="md">
    <div class="p-6">
      <h2 class="text-lg font-medium">Sign Contract</h2>
      <div class="mt-4">
        <div
          id="signature-pad"
          class="border rounded-lg"
          style="width: 100%; height: 200px;"
        ></div>
        <div class="mt-4 flex justify-end space-x-2">
          <x-filament::button color="secondary" wire:click="clearSignature">
            Clear
          </x-filament::button>
          <x-filament::button wire:click="saveSignature">
            Save Signature
          </x-filament::button>
        </div>
      </div>
    </div>
  </x-filament::modal>

  @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script>
      let signaturePad;

      document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.querySelector('#signature-pad');

        // Set canvas size to match container
        canvas.width = canvas.offsetWidth;
        canvas.height = canvas.offsetHeight;

        signaturePad = new SignaturePad(canvas, {
          backgroundColor: 'rgb(255, 255, 255)',
          penColor: 'rgb(0, 0, 0)'
        });
      });

      window.addEventListener('clear-signature', () => {
        signaturePad.clear();
      });

      Livewire.on('saveSignature', () => {
      @this.set('signature', signaturePad.toDataURL());
      });

      // Handle window resize
      window.addEventListener('resize', () => {
        const canvas = document.querySelector('#signature-pad');
        if (canvas) {
          const ratio = Math.max(window.devicePixelRatio || 1, 1);
          canvas.width = canvas.offsetWidth * ratio;
          canvas.height = canvas.offsetHeight * ratio;
          canvas.getContext('2d').scale(ratio, ratio);
          signaturePad.clear();
        }
      });
    </script>
  @endpush
</x-filament-panels::page>
