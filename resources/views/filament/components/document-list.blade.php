<div class="space-y-4">
  @foreach($media as $mediaItem)
    @if(!$mediaItem) @continue @endif

    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
      <div class="flex items-center space-x-4">
        {!! $getFileTypeIcon($mediaItem->mime_type) !!}
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-900 truncate">
            {{ $mediaItem->file_name }}
          </p>
          <p class="text-sm text-gray-500">
            {{ \Illuminate\Support\Number::fileSize($mediaItem->size) }} •
            Uploaded {{ $mediaItem->created_at->format(config('app.date_format', 'M d, Y')) }}
          </p>
        </div>
      </div>
      <div class="flex space-x-2">
        {!! $renderPreviewButton($mediaItem) !!}
        <a href="{{ $mediaItem->getUrl() }}"
           download
           class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-primary-600 focus:z-10 focus:ring-4 focus:ring-gray-200">
          Download
          <svg class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z" />
            <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z" />
          </svg>
        </a>
      </div>
    </div>
  @endforeach
</div>
