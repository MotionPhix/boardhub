<x-filament::page>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div class="flex items-center space-x-4">
        <x-filament::input.wrapper>
          <x-filament::input.select
            wire:model="previewMode"
            :options="['web' => 'Web View', 'pdf' => 'PDF Preview']"
          />
        </x-filament::input.wrapper>

        <x-filament::input.wrapper>
          <x-filament::input.select
            wire:model="selectedVersion"
            :options="$versions->pluck('created_at', 'id')->map(fn($date) => 'Version ' . $loop->iteration . ' - ' . $date->format('M j, Y H:i'))"
          />
        </x-filament::input.wrapper>
      </div>

      <div>
        @if($contract->signed_at)
          <x-filament::badge color="success">
            Signed on {{ $contract->signed_at->format('M j, Y') }}
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
        <iframe
          src="{{ route('contracts.preview-pdf', $contract) }}"
          class="w-full"
          style="height: 800px;"
        ></iframe>
      @endif
    </div>

    @if($contract->signatures)
      <div class="mt-4 space-y-4">
        <h3 class="text-lg font-medium">Signatures</h3>
        <div class="grid grid-cols-2 gap-4">
          @foreach($contract->signatures as $type => $signature)
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
        <div id="signature-pad" class="border rounded-lg"></div>
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
        signaturePad = new SignaturePad(canvas);
      });

      window.addEventListener('clear-signature', () => {
        signaturePad.clear();
      });

      Livewire.on('saveSignature', () => {
      @this.set('signature', signaturePad.toDataURL());
      });
    </script>
  @endpush
</x-filament::page>
