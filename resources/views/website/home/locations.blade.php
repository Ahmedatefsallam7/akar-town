{{--
    "Prime Destinations" (the design's .loc): featured locations in a draggable, auto-scrolling row; a card widens on
    hover and shows its description, and the section's blurred backdrop takes its photo.
    Behaviour: resources/js/website/locations.js.
    @var string $title  the home section's title (Sliders)
    @var list<array> $locations  App\Support\Website\FeaturedLocations::get()
--}}
@php
    $reveal = 'opacity-0 [transform:translateY(24px)]';
    $navButton = 'flex size-10 cursor-pointer items-center justify-center rounded-full border border-ink-10 bg-transparent p-0 text-ink transition-all duration-300 ease-smooth hover:scale-106 hover:border-ink hover:bg-ink hover:text-paper disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-25';
@endphp

<section id="locations" x-data="locationsSlider" class="relative overflow-hidden border-t border-ink-10 bg-page pt-20 pb-[100px] max-md:pt-[60px] max-md:pb-20">
    <div x-ref="ambient" class="pointer-events-none absolute inset-0 z-0 bg-cover bg-center opacity-0 transition-opacity duration-900 [filter:blur(55px)_saturate(130%)] [transform:scale(1.2)]" aria-hidden="true"></div>

    <div class="relative z-1 mx-auto max-w-[1340px] px-[60px] max-md:px-5">
        <header class="mb-[52px] flex items-end justify-between gap-10 max-md:mb-9 max-md:flex-col max-md:items-start max-md:gap-4">
            <div class="flex-1">
                <p data-loc-reveal class="{{ $reveal }} mb-[22px] inline-flex items-center rounded-[20px] border border-white/65 bg-white/52 px-4 py-1.5 font-sans text-[9.5px] font-bold tracking-[0.3em] text-brand-dk uppercase shadow-[0_2px_16px_rgba(0,0,0,0.08),0_1px_4px_rgba(0,0,0,0.05)] backdrop-blur-[20px] backdrop-saturate-180 dark:text-ink-60 rtl:font-arabic">{{ __('Locations') }}</p>
                <h2 data-loc-reveal class="{{ $reveal }} m-0 mb-3 font-sans text-[clamp(34px,3.8vw,52px)] leading-[1.08] font-light tracking-[-0.025em] text-ink rtl:font-arabic [&_em]:font-sans [&_em]:text-[1.04em] [&_em]:font-normal [&_em]:tracking-[-0.01em] [&_em]:text-brand [&_em]:not-italic rtl:[&_em]:font-arabic">{!! \App\Support\Website\HomeSections::accent($title) !!}</h2>
                <p data-loc-reveal class="{{ $reveal }} max-w-[400px] text-[15px] leading-[1.65] text-ink-60">{{ __('Handpicked neighbourhoods where luxury meets lifestyle.') }}</p>
            </div>
            <div data-loc-reveal class="{{ $reveal }} shrink-0 pb-1">
                <x-website.view-all :href="route('locations')">{{ __('View All Locations') }}</x-website.view-all>
            </div>
        </header>

        <div x-ref="wrap" class="-mx-[60px] cursor-grab overflow-x-auto overflow-y-hidden px-[60px] pb-5 [scrollbar-width:none] active:cursor-grabbing max-md:-mx-5 max-md:px-5 max-md:pb-4 [&::-webkit-scrollbar]:hidden">
            <div class="flex w-max gap-[18px] select-none">
                @foreach ($locations as $location)
                    <a
                        href="{{ $location['url'] }}"
                        data-loc-reveal
                        draggable="false"
                        @mouseenter="ambient(@js($location['image']))"
                        @mouseleave="ambient(null)"
                        class="{{ $reveal }} group/loc relative block h-[460px] w-[320px] shrink-0 overflow-hidden rounded-2xl bg-ink-10 [transition:width_0.58s_cubic-bezier(0.16,1,0.3,1),box-shadow_0.48s_ease] hover:w-[490px] max-md:h-[400px] max-md:w-[280px] max-md:hover:w-[490px]"
                    >
                        @if ($location['image'])
                            <img src="{{ $location['image'] }}" alt="{{ $location['name'] }}" loading="lazy" draggable="false" class="absolute inset-0 block size-full object-cover transition-transform duration-700 ease-out-ex group-hover/loc:scale-106" />
                        @endif
                        <div class="pointer-events-none absolute inset-0 z-1 bg-[linear-gradient(rgba(0,0,0,0.28)_0%,rgba(0,0,0,0)_28%)]"></div>
                        <div class="absolute inset-0 z-1 bg-[linear-gradient(rgba(0,0,0,0)_30%,rgba(0,0,0,0.52)_62%,rgba(0,0,0,0.86)_100%)] transition-[background] duration-400 group-hover/loc:bg-[linear-gradient(rgba(0,0,0,0.08)_0%,rgba(0,0,0,0.62)_50%,rgba(0,0,0,0.92)_100%)]"></div>
                        <div class="absolute inset-x-0 bottom-0 z-2 flex flex-col px-6 py-7">
                            @if ($location['country'])
                                <div class="mb-[10px] flex items-center gap-[7px]">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" class="size-[13px] shrink-0 fill-none stroke-brand stroke-[1.5] [stroke-linecap:round] [stroke-linejoin:round]">
                                        <path d="M12 21s-8-7.5-8-12a8 8 0 0116 0c0 4.5-8 12-8 12z" />
                                        <circle cx="12" cy="9" r="2.5" />
                                    </svg>
                                    <span class="font-sans text-[9.5px] font-semibold tracking-[0.22em] text-brand-lt uppercase rtl:font-arabic">{{ $location['country'] }}</span>
                                </div>
                            @endif
                            <h3 class="mb-4 font-sans text-[clamp(22px,2vw,28px)] leading-[1.1] font-light tracking-[-0.02em] text-paper rtl:font-arabic">{{ $location['name'] }}</h3>
                            @if ($location['description'])
                                <p class="mb-0 max-h-0 overflow-hidden text-[12.5px] leading-[1.6] text-white/58 opacity-0 [transition:max-height_0.5s_cubic-bezier(0.16,1,0.3,1),opacity_0.4s_ease_0.08s,margin-bottom_0.4s_ease] group-hover/loc:mb-[14px] group-hover/loc:max-h-[52px] group-hover/loc:opacity-100">{{ \Illuminate\Support\Str::limit(strip_tags($location['description']), 110) }}</p>
                            @endif
                            <div class="flex items-center justify-between gap-3">
                                <span class="inline-flex items-center gap-[7px] font-sans text-[11px] font-medium tracking-[0.12em] text-white/65 uppercase rtl:font-arabic">
                                    <span class="size-[5px] shrink-0 rounded-full bg-brand"></span>
                                    {{ trans_choice(':count Property|:count Properties', $location['count'], ['count' => $location['count']]) }}
                                </span>
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-full border border-white/22 bg-white/8 text-paper backdrop-blur-[8px] transition-all duration-300 ease-out-ex group-hover/loc:border-brand group-hover/loc:bg-brand">
                                    <x-website.icons.arrow class="size-[14px] stroke-[1.8] transition-transform duration-300 ease-out-ex group-hover/loc:translate-x-[3px] rtl:-scale-x-100 rtl:group-hover/loc:-translate-x-[3px]" />
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        <div data-loc-reveal class="{{ $reveal }} mt-10 flex items-center gap-5">
            <button type="button" @click="prev()" :disabled="atStart" class="{{ $navButton }}" aria-label="{{ __('Previous locations') }}">
                <x-website.icons.arrow class="size-[15px] -scale-x-100 stroke-[1.8] rtl:scale-x-100" />
            </button>
            <div class="h-0.5 flex-1 overflow-hidden rounded-[2px] bg-ink-10">
                <div class="h-full rounded-[2px] bg-brand transition-[width] duration-250" :style="`width: ${progress}%`"></div>
            </div>
            <button type="button" @click="next()" :disabled="atEnd" class="{{ $navButton }}" aria-label="{{ __('Next locations') }}">
                <x-website.icons.arrow class="size-[15px] stroke-[1.8] rtl:-scale-x-100" />
            </button>
        </div>
    </div>
</section>
