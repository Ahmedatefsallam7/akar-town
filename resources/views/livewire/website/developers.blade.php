{{--
    The developers page (the design's developers.html): header, the cards appearing as they scroll in, and "Load More".
--}}
<div>
    <x-website.page-hero :crumb="__('Developers')" :title="__('Explore <em>Trusted</em> Developers')" :count="$total" :count-label="__('Trusted Partners')" :animate="false" />

    <div class="mx-auto max-w-[1320px] px-12 pt-12 {{ $more ? 'pb-0' : 'pb-12 max-md:pb-[60px]' }} max-[1100px]:px-7 max-md:px-5 max-md:pt-6">
        <div class="grid grid-cols-3 gap-6 max-[1100px]:grid-cols-2 max-md:grid-cols-1" aria-label="{{ __('Developers') }}">
            @forelse ($cards as $i => $card)
                <div
                    wire:key="developer-{{ $card['slug'] }}"
                    x-data="{ shown: false }"
                    x-intersect.once.threshold.08="shown = true"
                    :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-7'"
                    style="transition-delay: {{ ($i % 6) * 60 }}ms"
                    class="flex transition-[opacity,translate] duration-650 ease-out-ex [&>article]:flex-1"
                >
                    <x-website.developer-card :card="$card" />
                </div>
            @empty
                <div class="col-span-full px-6 py-20 text-center" role="status">
                    <svg class="mx-auto mb-5 size-16 text-ink-30" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="28" /><path d="M22 32h20M32 22v20" stroke-linecap="round" /></svg>
                    <p class="mb-2 font-sans text-[1.6rem] font-light text-ink rtl:font-arabic">{{ __('No developers found') }}</p>
                    <p class="text-[13px] text-ink-30">{{ __('Please check back soon.') }}</p>
                </div>
            @endforelse
        </div>

        @if ($more)
            <x-website.load-more>{{ __('Load More Developers') }}</x-website.load-more>
        @endif
    </div>
</div>
