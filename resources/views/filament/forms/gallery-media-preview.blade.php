{{--
    A property's image taken from its gallery (featured image, 360°, floor plans), shown in place of its own upload
    field while the picked gallery has that kind (UnitResource::fromGallery()).
--}}
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="rounded-lg border border-gray-200 p-3 dark:border-white/10">
        <div class="flex flex-wrap gap-2">
            @foreach ($media as $item)
                <img
                    src="{{ $item->getAvailableUrl(['thumb']) }}"
                    alt="{{ $item->name }}"
                    loading="lazy"
                    class="h-24 w-36 rounded-md object-cover ring-1 ring-gray-950/10 dark:ring-white/10"
                >
            @endforeach
        </div>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {{ __('admin.units.from_gallery', ['gallery' => $galleryName]) }}
            @if ($editUrl)
                <a href="{{ $editUrl }}" target="_blank" class="font-medium text-primary-600 hover:underline dark:text-primary-400">{{ __('admin.units.edit_gallery') }}</a>
            @endif
        </p>
    </div>
</x-dynamic-component>
