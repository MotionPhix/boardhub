<x-filament-panels::page>
  <div x-data="{}" x-init="$store.sidebar = $store.sidebar ?? {
        isOpen: true,
        isCollapsed: false,
        collapsedGroups: [],
        groupIsCollapsed(group) {
            return this.collapsedGroups.includes(group)
        },
        toggleCollapsedGroup(group) {
            if (this.groupIsCollapsed(group)) {
                this.collapsedGroups = this.collapsedGroups.filter(g => g !== group)
            } else {
                this.collapsedGroups = [...this.collapsedGroups, group]
            }
        },
        close() { this.isOpen = false },
        open() { this.isOpen = true },
        toggle() { this.isOpen = !this.isOpen },
        collapse() { this.isCollapsed = true },
        expand() { this.isCollapsed = false },
        toggleCollapsed() { this.isCollapsed = !this.isCollapsed }
    }">
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div class="flex items-center space-x-4">
        <x-filament::input.wrapper>
          <x-filament::input.select wire:model.live="previewMode">
            <option value="web">Web View</option>
            <option value="pdf">PDF Preview</option>
          </x-filament::input.select>
        </x-filament::input.wrapper>
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
          @include('contracts.web-contract-template', [
            'contract'       => $record,
            'settings'       => app(App\Models\Settings::class),
            'localization'   => App\Models\Settings::getLocalization(),
            'generatedBy'    => auth()->user()->name ?? 'System',
            'date'           => now()
                                  ->setTimezone(App\Models\Settings::getLocalization()['timezone'])
                                  ->format(App\Models\Settings::getLocalization()['date_format'] . ' ' . App\Models\Settings::getLocalization()['time_format'])
          ])
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

    @if($record->hasBeenSigned())
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

  @if (!$record->hasBeenSigned())
    <x-filament::modal id="signatureModal" width="md">
      <form action="{{ $record->getSignatureRoute() }}" method="POST">
        @csrf
        <div class="p-6">
          <h2 class="text-lg font-medium mb-4">Sign Contract</h2>

          <x-creagia-signature-pad
            border-color="#e5e7eb"
            pad-classes="rounded-lg border-2 w-full"
            button-classes="mt-4 inline-flex items-center justify-center gap-1 font-medium rounded-lg border transition-colors outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset min-h-[2.25rem] px-4 text-sm text-white shadow focus:ring-white border-transparent bg-primary-600 hover:bg-primary-500 focus:bg-primary-700 focus:ring-offset-primary-700"
            clear-name="Clear"
            submit-name="Save Signature"
            :disabled-without-signature="true"
          />
        </div>
      </form>
    </x-filament::modal>
  @endif

  @pushOnce('scripts')
    <script src="{{ asset('vendor/sign-pad/sign-pad.min.js') }}"></script>
  @endPushOnce
  </div>
</x-filament-panels::page>
