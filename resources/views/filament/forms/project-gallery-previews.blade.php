<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2">
        @foreach ($galleries as $gallery)
            <section class="w-fit max-w-full rounded-lg border border-gray-200 p-3 dark:border-white/10">
                <h3 class="mb-2 text-sm font-medium text-gray-950 dark:text-white">{{ $gallery->getLocalized('name') }}</h3>

                @if ($gallery->media->isNotEmpty())
                    <div class="flex max-w-full flex-wrap gap-2">
                        @foreach ($gallery->media as $photo)
                            <img
                                src="{{ $photo->getAvailableUrl(['thumb']) }}"
                                alt="{{ $gallery->getLocalized('name') }}"
                                loading="lazy"
                                class="h-20 w-28 rounded-md object-cover ring-1 ring-gray-950/10 dark:ring-white/10"
                            >
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.galleries.photos_empty') }}</p>
                @endif
            </section>
        @endforeach
    </div>
</x-dynamic-component>
