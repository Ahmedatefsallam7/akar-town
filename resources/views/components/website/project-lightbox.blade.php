{{--
    The "Our Projects" photo lightbox (the design's .pgal-lb): image, prev / next at the screen edges, title and
    counter, thumbnails. Opened with $store.projectLightbox.show(images, title).
--}}
<div
    x-data
    @keydown.window.escape="$store.projectLightbox.open && $store.projectLightbox.close()"
    @keydown.window.arrow-left="$store.projectLightbox.open && $store.projectLightbox.prev()"
    @keydown.window.arrow-right="$store.projectLightbox.open && $store.projectLightbox.next()"
    @click.self="$store.projectLightbox.close()"
    class="pointer-events-none fixed inset-0 z-9999 flex items-center justify-center bg-[rgba(10,10,10,0.96)] opacity-0 transition-opacity duration-300"
    :class="{ 'pointer-events-none opacity-0': ! $store.projectLightbox.open, 'pointer-events-auto opacity-100': $store.projectLightbox.open }"
    role="dialog"
    aria-modal="true"
    aria-label="{{ __('Property gallery') }}"
>
    <div class="relative flex h-[min(80vh,700px)] w-[min(90vw,1100px)] items-center justify-center">
        <button type="button" @click="$store.projectLightbox.close()" class="absolute -top-11 right-0 cursor-pointer border-none bg-none text-[28px] leading-none text-white/70 transition-colors duration-200 hover:text-white" aria-label="{{ __('Close') }}">&#x2715;</button>
        <img
            :src="$store.projectLightbox.images[$store.projectLightbox.index]"
            x-on:load="$el.style.opacity = 1"
            x-effect="$store.projectLightbox.index; $el.style.opacity = 0"
            alt=""
            class="max-h-full max-w-full rounded-lg object-contain transition-opacity duration-200"
        />
    </div>

    @php($nav = 'fixed top-1/2 flex size-12 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-white/15 bg-white/10 text-white backdrop-blur-[8px] transition-[background] duration-200 hover:bg-white/20')
    <button type="button" @click="$store.projectLightbox.prev()" :disabled="$store.projectLightbox.index === 0" class="{{ $nav }} left-6 rtl:right-6 rtl:left-auto" aria-label="{{ __('Previous') }}">
        <x-website.icons.arrow class="size-[18px] -scale-x-100 stroke-[1.5] rtl:scale-x-100" />
    </button>
    <button type="button" @click="$store.projectLightbox.next()" :disabled="$store.projectLightbox.index === $store.projectLightbox.images.length - 1" class="{{ $nav }} right-6 rtl:right-auto rtl:left-6" aria-label="{{ __('Next') }}">
        <x-website.icons.arrow class="size-[18px] stroke-[1.5] rtl:-scale-x-100" />
    </button>

    <div class="fixed bottom-7 left-1/2 -translate-x-1/2 text-center">
        <p class="mb-1.5 font-sans text-[15px] font-light tracking-[-0.01em] text-white rtl:font-arabic" x-text="$store.projectLightbox.title"></p>
        <p class="text-[11px] tracking-[0.1em] text-white/45" x-text="($store.projectLightbox.index + 1) + ' / ' + $store.projectLightbox.images.length"></p>
    </div>

    <div class="fixed bottom-20 left-1/2 flex -translate-x-1/2 gap-2">
        <template x-for="(image, i) in $store.projectLightbox.images" :key="i">
            <img
                :src="image"
                alt=""
                @click="$store.projectLightbox.index = i"
                class="h-[38px] w-[52px] cursor-pointer rounded border-2 object-cover transition-[opacity,border-color] duration-200"
                :class="i === $store.projectLightbox.index ? 'border-brand opacity-100' : 'border-transparent opacity-45 hover:opacity-80'"
            />
        </template>
    </div>
</div>
