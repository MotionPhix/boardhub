@php
  $contractDocuments = $record->getMedia('contract_documents');
  $signedContract = $record->getFirstMedia('signed_contracts');
@endphp

@if($contractDocuments->isNotEmpty() || $signedContract)
  <div class="space-y-6">
    @if($contractDocuments->isNotEmpty())
      <div class="space-y-2">
        <h3 class="text-lg font-medium text-gray-900">Uploaded Documents</h3>
        <div class="grid gap-4">
          @foreach($contractDocuments as $document)
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
              <div class="flex items-center space-x-3">
                {{-- Document Icon based on type --}}
                <div class="flex-shrink-0">
                  @if(str_contains($document->mime_type, 'pdf'))
                    <svg class="w-8 h-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                  @else
                    <svg class="w-8 h-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                  @endif
                </div>

                {{-- Document Info --}}
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 truncate">
                    {{ $document->file_name }}
                  </p>
                  <p class="text-sm text-gray-500">
                    {{ \Illuminate\Support\Number::fileSize($document->size) }} •
                    Added {{ $document->created_at->diffForHumans() }}
                  </p>
                </div>
              </div>

              {{-- Download Button --}}
              <div class="ml-4">
                <a href="{{ $document->getUrl() }}"
                   download
                   class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-primary-600 focus:z-10 focus:ring-4 focus:ring-gray-200">
                  <span>Download</span>
                  <svg class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z" />
                    <path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" />
                  </svg>
                </a>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @endif

    @if($signedContract)
      <div class="space-y-2">
        <h3 class="text-lg font-medium text-gray-900">Signed Contract</h3>
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
          <div class="flex items-center space-x-3">
            {{-- PDF Icon --}}
            <div class="flex-shrink-0">
              <svg class="w-8 h-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
            </div>

            {{-- Document Info --}}
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">
                {{ $signedContract->file_name }}
              </p>
              <p class="text-sm text-gray-500">
                {{ \Illuminate\Support\Number::fileSize($signedContract->size) }} •
                Added {{ $signedContract->created_at->diffForHumans() }}
              </p>
            </div>
          </div>

          {{-- Download Button --}}
          <div class="ml-4">
            <a href="{{ $signedContract->getUrl() }}"
               download
               class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-primary-600 focus:z-10 focus:ring-4 focus:ring-gray-200">
              <span>Download</span>
              <svg class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z" />
                <path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" />
              </svg>
            </a>
          </div>
        </div>
      </div>
    @endif
  </div>
@endif
