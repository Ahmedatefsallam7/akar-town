{{--
    An image's shape, drawn, with its size inside (Settings → Image Sizes: the cards and the edit window's preview).
    $drawing: the shape's classes; $thin: a very wide shape, too low for the icon and the ratio.
--}}
<div class="flex h-44 items-center justify-center bg-gray-50 p-5 dark:bg-white/5">
    <div class="{{ $drawing }} flex flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-primary-400 bg-primary-50 text-primary-700 transition-all duration-300 dark:border-primary-500/60 dark:bg-primary-500/10 dark:text-primary-300">
        @unless ($thin)
            <x-filament::icon icon="heroicon-o-photo" class="size-6 opacity-70" />
        @endunless
        <span class="text-base font-bold leading-none" dir="ltr">{{ $size }}</span>
        @unless ($thin)
            <span class="text-xs font-medium opacity-70" dir="ltr">{{ $ratio }}</span>
        @endunless
    </div>
</div>
