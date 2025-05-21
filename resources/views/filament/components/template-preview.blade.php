@if($template)
  <div class="rounded-lg overflow-hidden border border-gray-200">
    @if($template->preview_image_url)
      <img src="{{ $template->preview_image_url }}"
           alt="{{ $template->name }}"
           class="w-full h-auto object-cover"
           style="max-height: 200px;">
    @endif

    <div class="p-4">
      <h3 class="text-lg font-medium text-gray-900">
        {{ $template->name }}
      </h3>

      @if($template->description)
        <p class="mt-1 text-sm text-gray-500">
          {{ $template->description }}
        </p>
      @endif

      @if($template->variables)
        <div class="mt-4">
          <h4 class="text-sm font-medium text-gray-900">Available Variables:</h4>
          <ul class="mt-2 grid grid-cols-2 gap-2">
            @foreach($template->variables as $variable)
              <li class="text-sm text-gray-500">
                <span class="font-mono">{{ $variable['name'] }}</span>
                - {{ $variable['description'] }}
              </li>
            @endforeach
          </ul>
        </div>
      @endif
    </div>
  </div>
@endif
