{{--
    The locations page (the design's locations.html): header, the cards (photo, the place above it, name, number of
    properties; featured ones carry a badge) appearing as they scroll in, and "Load More".
--}}
<div>
    <x-website.page-hero :crumb="__('Locations')" :title="__('Explore <em>Prime</em> Locations')" :count="$total" :count-label="__('Locations')" :animate="false" start-on-phone />

    <main class="mx-auto max-w-[1320px] px-12 pt-12 {{ $more ? 'pb-2' : 'pb-12' }} max-[1100px]:px-7 max-[700px]:px-5" aria-label="{{ __('Locations listing') }}">
        <div class="grid grid-cols-3 gap-[18px] max-[1100px]:grid-cols-2 max-[1100px]:gap-[14px] max-[700px]:grid-cols-1 max-[700px]:gap-3">
            @forelse ($cards as $i => $card)
                {{-- The outer block scrolls in; the card itself lifts on hover (as in the design). --}}
                <div
                    wire:key="location-{{ $card['slug'] }}"
                    x-data="{ shown: false }"
                    x-intersect.once.threshold.08="shown = true"
                    :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-7'"
                    style="transition-delay: {{ ($i % 6) * 60 }}ms"
                    class="transition-[opacity,translate] duration-650 ease-out-ex"
                >
                    <a
                        href="{{ $card['url'] }}"
                        class="group/loc relative block cursor-pointer overflow-hidden rounded-[20px] no-underline transition-transform duration-450 ease-out-ex after:absolute after:inset-x-0 after:bottom-0 after:z-5 after:h-[3px] after:origin-left after:scale-x-0 after:bg-brand after:transition-transform after:duration-500 after:ease-out-ex hover:-translate-y-2 hover:after:scale-x-100 rtl:after:origin-right"
                    >
                        <div class="relative aspect-square overflow-hidden bg-ink-10 max-[700px]:aspect-video">
                            @if ($card['image'])
                                <img src="{{ $card['image'] }}" alt="{{ $card['name'] }}" loading="lazy" class="block size-full object-cover transition-transform duration-800 ease-smooth group-hover/loc:scale-109" />
                            @endif
                            <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_top,rgba(4,3,8,0.92)_0%,rgba(4,3,8,0.55)_35%,rgba(4,3,8,0.12)_62%,transparent_100%)]"></div>

                            @if ($card['featured'])
                                <span class="absolute top-4 left-4 z-4 rounded-md bg-brand px-[11px] py-[5px] text-[8.5px] font-bold tracking-[0.14em] text-white uppercase rtl:right-4 rtl:left-auto rtl:font-arabic rtl:tracking-normal">{{ __('Featured') }}</span>
                            @endif

                            <div class="absolute inset-x-0 bottom-0 z-3 px-[22px] pb-[22px]">
                                @if ($card['region'])
                                    <span class="mb-1.5 block text-[9px] font-bold tracking-[0.18em] text-white/52 uppercase rtl:font-arabic rtl:tracking-normal">{{ $card['region'] }}</span>
                                @endif
                                <div class="mb-[14px] font-sans text-[22px] leading-[1.1] font-bold tracking-[-0.02em] text-white max-[1100px]:text-[19px] max-[700px]:text-[20px] rtl:font-arabic rtl:tracking-normal">{{ $card['name'] }}</div>
                                <div class="flex items-center justify-between">
                                    <span class="rounded-full border border-white/16 bg-white/11 px-[13px] py-1.5 text-[10px] font-bold tracking-[0.08em] text-white/82 uppercase backdrop-blur-[14px] backdrop-saturate-160 rtl:font-arabic rtl:tracking-normal">{{ trans_choice(':count Property|:count Properties', $card['count'], ['count' => number_format($card['count'])]) }}</span>
                                    <span class="flex size-[34px] shrink-0 items-center justify-center rounded-full border border-white/22 bg-white/9 text-white backdrop-blur-[14px] transition-[background,border-color,translate] duration-350 ease-out-ex group-hover/loc:translate-x-1 group-hover/loc:border-brand group-hover/loc:bg-brand rtl:group-hover/loc:-translate-x-1">
                                        <svg width="13" height="13" viewBox="0 0 13 13" fill="none" aria-hidden="true" class="rtl:-scale-x-100"><path d="M2.5 6.5h8M6.5 2.5l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-span-full px-5 py-20 text-center" role="status">
                    <p class="mb-2 text-[18px] font-medium text-ink rtl:font-arabic">{{ __('No locations found') }}</p>
                    <p class="text-[14px] text-ink-60">{{ __('Please check back soon.') }}</p>
                </div>
            @endforelse
        </div>
    </main>

    @if ($more)
        <x-website.load-more>{{ __('Load More Locations') }}</x-website.load-more>
    @endif
</div>
