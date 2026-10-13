{{--
    Photo lightbox (the design's .gallery-lb): big image, prev / next, thumbnails strip and counter. Opened with
    $store.lightbox.show(images); Escape closes, the arrow keys move.
--}}
<div
    x-data
    @keydown.window.escape="$store.lightbox.open && $store.lightbox.close()"
    @keydown.window.arrow-left="$store.lightbox.open && $store.lightbox.prev()"
    @keydown.window.arrow-right="$store.lightbox.open && $store.lightbox.next()"
    @click.self="$store.lightbox.close()"
    class="pointer-events-none fixed inset-0 z-2000 flex items-center justify-center overflow-y-auto bg-black/96 opacity-0 transition-opacity duration-320"
    :class="{ 'pointer-events-none opacity-0': ! $store.lightbox.open, 'pointer-events-auto opacity-100': $store.lightbox.open }"
    role="dialog"
    aria-modal="true"
    aria-label="{{ __('Property gallery') }}"
>
    <button
        type="button"
        @click="$store.lightbox.close()"
        class="absolute top-6 right-7 flex size-[42px] cursor-pointer items-center justify-center rounded-full border border-white/15 bg-white/10 text-white transition-[background] duration-200 hover:bg-white/20"
        aria-label="{{ __('Close gallery') }}"
    >
        <x-website.icons.close width="18" height="18" stroke-width="1.8" />
    </button>

    <div class="relative w-full max-w-[min(900px,88vw)]">
        @php($nav = 'absolute top-[calc(50%-46px)] flex size-11 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-white/15 bg-white/10 p-0 text-white transition-[background] duration-200 hover:bg-white/20 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-20')
        <button type="button" @click="$store.lightbox.prev()" :disabled="$store.lightbox.index === 0" class="{{ $nav }} -left-[60px] rtl:right-[-60px] rtl:left-auto" aria-label="{{ __('Previous image') }}">
            <x-website.icons.arrow class="size-[18px] -scale-x-100 stroke-[1.8] rtl:scale-x-100" />
        </button>
        <img :src="$store.lightbox.images[$store.lightbox.index]" :alt="@js(__('Property image')) + ' ' + ($store.lightbox.index + 1)" class="block max-h-[68vh] w-full rounded-lg object-contain" />
        <button type="button" @click="$store.lightbox.next()" :disabled="$store.lightbox.index === $store.lightbox.images.length - 1" class="{{ $nav }} -right-[60px] rtl:right-auto rtl:left-[-60px]" aria-label="{{ __('Next image') }}">
            <x-website.icons.arrow class="size-[18px] stroke-[1.8] rtl:-scale-x-100" />
        </button>

        <div class="mt-[14px] flex flex-wrap justify-center gap-2">
            <template x-for="(image, i) in $store.lightbox.images" :key="i">
                <img
                    :src="image"
                    alt=""
                    @click="$store.lightbox.index = i"
                    class="h-[46px] w-16 shrink-0 cursor-pointer rounded border-2 object-cover transition-[opacity,border-color] duration-200"
                    :class="i === $store.lightbox.index ? 'border-brand opacity-100' : 'border-transparent opacity-45 hover:opacity-100'"
                />
            </template>
        </div>
        <p class="mt-[10px] text-center font-sans text-[11px] tracking-[0.12em] text-white/35" x-text="($store.lightbox.index + 1) + ' / ' + $store.lightbox.images.length"></p>
    </div>
</div>
