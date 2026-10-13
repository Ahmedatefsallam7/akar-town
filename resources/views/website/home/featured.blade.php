{{--
    Featured properties (the design's .props): a split card (photo + details) switching between the featured units,
    with favourite / compare / gallery actions. Behaviour: resources/js/website/featured.js.
    @var string $title  the home section's title (Sliders)
    @var list<array> $featured  App\Support\Website\FeaturedUnits::get()
--}}
@php
    $reveal = 'opacity-0 [transform:translateY(30px)]';
    $action = 'relative flex size-9 cursor-pointer items-center justify-center rounded-full border bg-transparent p-0 transition-all duration-250 ease-smooth';
    $actionIcon = 'pointer-events-none size-[15px] fill-none stroke-current stroke-[1.6] transition-all duration-250 ease-smooth [stroke-linecap:round] [stroke-linejoin:round]';
    $thumbArrow = 'flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-full border border-ink-30 bg-page p-0 text-ink transition-all duration-280 hover:border-ink hover:bg-ink hover:text-paper';
@endphp

<section id="properties" x-data="featuredUnits(@js($featured))" @mouseenter="stop()" @mouseleave="start()" class="relative bg-page pt-[72px] pb-[90px]">
    <div class="mx-auto max-w-[1340px] px-[60px] max-[900px]:px-6 max-md:px-5">
        <header class="mb-12 flex items-end justify-between gap-10 max-md:flex-col max-md:gap-4">
            <div class="flex-1">
                <p data-reveal-up class="{{ $reveal }} mb-[22px] inline-flex items-center rounded-[20px] border border-white/65 bg-white/52 px-4 py-1.5 font-sans text-[9.5px] font-bold tracking-[0.3em] text-brand-dk uppercase shadow-[0_2px_16px_rgba(0,0,0,0.08),0_1px_4px_rgba(0,0,0,0.05)] backdrop-blur-[20px] backdrop-saturate-180 dark:text-ink-60 rtl:font-arabic">{{ __('Properties') }}</p>
                <h2 data-reveal-up class="{{ $reveal }} mt-0 mb-3 font-sans text-[clamp(34px,3.8vw,52px)] leading-[1.08] font-light tracking-[-0.025em] text-ink rtl:font-arabic max-[480px]:text-[clamp(28px,8vw,48px)] [&_em]:font-sans [&_em]:text-[1.04em] [&_em]:font-normal [&_em]:tracking-[-0.01em] [&_em]:text-brand [&_em]:not-italic rtl:[&_em]:font-arabic">{!! \App\Support\Website\HomeSections::accent($title) !!}</h2>
                <p data-reveal-up class="{{ $reveal }} max-w-[420px] text-[15px] leading-[1.65] text-ink-60">{{ __('Exceptional homes curated exclusively for the discerning few.') }}</p>
            </div>
            <x-website.view-all :href="route('properties')" data-reveal-up class="{{ $reveal }}">{{ __('View All Properties') }}</x-website.view-all>
        </header>

        <div class="-mb-20 overflow-hidden pb-20 max-lg:mb-0! max-lg:pb-0!">
            <div data-reveal-up class="{{ $reveal }} grid grid-cols-[48fr_52fr] items-start gap-12 overflow-visible dark:border-line dark:bg-surface-2 dark:shadow-[0_2px_16px_rgba(0,0,0,0.35)] dark:hover:border-line-md dark:hover:shadow-[0_8px_32px_rgba(0,0,0,0.55),0_0_0_1px_rgba(212,20,44,0.18)] max-lg:grid-cols-1! max-lg:gap-0!">
                {{-- Photo + the properties' thumbnails --}}
                <div class="relative pb-14 max-lg:pb-0!">
                    <div x-ref="mainWrap" class="group/img relative h-[515px] w-full overflow-hidden rounded-[20px] bg-ink-10 max-lg:h-[380px]! max-md:h-[280px]!">
                        <img x-ref="main" :src="current.images[0]" :alt="current.title" src="{{ $featured[0]['images'][0] ?? '' }}" alt="{{ $featured[0]['title'] }}" loading="eager" class="size-full object-cover transition-transform duration-700 ease-smooth will-change-[transform,opacity] group-hover/img:scale-103" />
                    </div>
                    <div class="absolute bottom-[75px] left-[26%] z-10 flex w-[calc(100%+280px)] items-center gap-3 rtl:right-[26%] rtl:left-auto max-lg:relative! max-lg:inset-auto! max-lg:mt-[10px]! max-lg:w-full! max-lg:p-0!">
                        <div class="flex min-w-0 flex-1 flex-nowrap gap-2">
                            @foreach ($featured as $i => $unit)
                                <button
                                    type="button"
                                    data-unit-thumb
                                    @click="go({{ $i }})"
                                    aria-label="{{ $unit['title'] }}"
                                    class="group/thumb relative aspect-[4/3] min-w-0 flex-1 basis-0 cursor-pointer overflow-hidden rounded-[7px] border-[3px] bg-none p-0 transition-[border-color] duration-300 after:pointer-events-none after:absolute after:inset-0 after:bg-[rgba(13,13,13,0.32)] after:transition-opacity after:duration-300 hover:after:opacity-0"
                                    :class="idx === {{ $i }} ? 'border-brand after:opacity-0' : 'border-white'"
                                >
                                    <img src="{{ $unit['images'][0] ?? '' }}" alt="" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" class="block size-full object-cover transition-transform duration-500 group-hover/thumb:scale-107" />
                                </button>
                            @endforeach
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            <button type="button" @click="prev()" :disabled="idx === 0" class="{{ $thumbArrow }}" aria-label="{{ __('Previous image') }}">
                                <x-website.icons.arrow class="size-[14px] -scale-x-100 stroke-[1.8] rtl:scale-x-100" />
                            </button>
                            <button type="button" @click="next()" :disabled="idx === items.length - 1" class="{{ $thumbArrow }}" aria-label="{{ __('Next image') }}">
                                <x-website.icons.arrow class="size-[14px] stroke-[1.8] rtl:-scale-x-100" />
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Details --}}
                <div x-ref="info" class="flex flex-col pt-1.5 max-lg:pt-6!">
                    <div class="mb-4 flex items-center justify-between gap-4">
                        <div class="flex flex-wrap gap-2">
                            <template x-for="badge in current.badges" :key="badge.label">
                                <span
                                    class="inline-flex items-center rounded-[3px] px-[14px] py-1.5 font-sans text-[10px] font-bold tracking-[0.15em] uppercase rtl:font-arabic"
                                    :class="{ 'bg-danger text-white': badge.style === 'red', 'bg-brand text-white': badge.style === 'gold', 'border border-ink-30 text-ink-60': badge.style === 'outline' }"
                                    x-text="badge.label"
                                ></span>
                            </template>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <button
                                type="button"
                                @click="$store.favourites.toggle(current.id); $el.style.transform = 'scale(1.35)'; setTimeout(() => $el.style.transform = '', 200)"
                                class="{{ $action }}"
                                :class="$store.favourites.has(current.id) ? 'border-[#e74c3c] text-[#e74c3c]' : 'border-ink-10 text-ink-60 hover:border-ink-30 hover:text-ink'"
                                :aria-label="$store.favourites.has(current.id) ? @js(__('Remove from Favourites')) : @js(__('Add to Favourites'))"
                                :title="$store.favourites.has(current.id) ? @js(__('Remove from Favourites')) : @js(__('Add to Favourites'))"
                            >
                                <svg viewBox="0 0 24 24" class="{{ $actionIcon }}" :class="$store.favourites.has(current.id) && 'fill-[#e74c3c]! stroke-[#e74c3c]'">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                                </svg>
                            </button>
                            <button
                                type="button"
                                @click="$store.compare.toggle({ id: current.id, title: current.title, thumb: current.images[0] })"
                                class="{{ $action }}"
                                :class="$store.compare.has(current.id) ? 'border-brand bg-[rgba(200,16,46,0.08)] text-brand' : 'border-ink-10 text-ink-60 hover:border-ink-30 hover:text-ink'"
                                :aria-label="$store.compare.has(current.id) ? @js(__('Remove from Compare')) : @js(__('Add to Compare'))"
                                :title="$store.compare.has(current.id) ? @js(__('Remove from Compare')) : @js(__('Add to Compare'))"
                            >
                                <x-website.icons.compare class="{{ $actionIcon }} stroke-2!" width="15" height="15" />
                            </button>
                            <button
                                type="button"
                                @click="$store.lightbox.show(current.images, 0)"
                                class="{{ $action }} border-ink-10 text-ink-60 hover:border-ink-30 hover:text-ink"
                                aria-label="{{ __('View Gallery') }}"
                                title="{{ __('View Gallery') }}"
                            >
                                <svg viewBox="0 0 24 24" class="{{ $actionIcon }}">
                                    <rect x="3" y="3" width="7" height="7" rx="1" />
                                    <rect x="14" y="3" width="7" height="7" rx="1" />
                                    <rect x="3" y="14" width="7" height="7" rx="1" />
                                    <rect x="14" y="14" width="7" height="7" rx="1" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <h3 class="mb-3 font-sans text-[clamp(26px,2.8vw,40px)] leading-[1.12] font-light tracking-[-0.02em] text-ink rtl:font-arabic" x-text="current.title">{{ $featured[0]['title'] }}</h3>
                    <div class="mt-1 mb-[14px] flex items-center gap-[7px] text-[13px] text-ink-60" x-show="current.location">
                        <svg viewBox="0 0 24 24" aria-hidden="true" class="size-[14px] shrink-0 fill-none stroke-brand stroke-[1.5] [stroke-linecap:round] [stroke-linejoin:round]">
                            <path d="M12 21s-8-7.5-8-12a8 8 0 0116 0c0 4.5-8 12-8 12z" />
                            <circle cx="12" cy="9" r="2.5" />
                        </svg>
                        <span x-text="current.location"></span>
                    </div>
                    <p class="mb-[14px] line-clamp-3 text-[14.5px] leading-[1.65] text-ink-60" x-text="current.description"></p>
                    <div class="mb-[14px] h-px bg-ink-10 dark:bg-line"></div>

                    <div class="mb-[14px] flex gap-7">
                        @foreach ([
                            ['beds', __('Beds'), '<path d="M2 9V19M22 9V19M2 14H22M6 14V10a1 1 0 011-1h4a1 1 0 011 1v4"/>'],
                            ['baths', __('Baths'), '<path d="M4 12h16v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4z"/><path d="M4 12V7a2 2 0 012-2h2v7"/><path d="M8 21v1M16 21v1"/>'],
                            ['area', __('Area m²'), '<rect x="3" y="3" width="18" height="18" rx="1.5"/><path d="M3 9h18M9 3v18"/>'],
                        ] as [$key, $label, $icon])
                            <div class="flex items-center gap-[10px]">
                                <span class="size-5 shrink-0 text-brand">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="size-full">{!! $icon !!}</svg>
                                </span>
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-[20px] leading-none font-semibold text-ink tabular-nums" x-text="current.{{ $key }} ?? '—'"></span>
                                    <span class="text-[10px] font-medium tracking-[0.14em] text-ink-60 uppercase">{{ $label }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mb-[14px] h-px bg-ink-10 dark:bg-line"></div>

                    <div class="flex items-end justify-between gap-4 max-md:flex-wrap max-md:gap-3">
                        <div>
                            <div class="mb-1.5 text-[10px] font-medium tracking-[0.2em] text-brand uppercase">{{ __('Starting Price') }}</div>
                            <div class="text-[clamp(24px,2.5vw,34px)] leading-none font-semibold text-brand-dk tabular-nums" x-text="current.price ?? '—'"></div>
                        </div>
                        <a :href="current.url" class="group inline-flex shrink-0 cursor-pointer items-center gap-2 rounded-full border-none bg-brand px-[30px] py-[14px] font-sans text-[13px] leading-none font-[650] tracking-[0.055em] whitespace-nowrap text-white transition-all duration-300 ease-out-ex hover:-translate-y-0.5 hover:bg-brand-lt hover:text-ink rtl:font-arabic">
                            {{ __('Explore') }}
                            <x-website.icons.arrow class="size-[14px] transition-transform duration-300 ease-out-ex group-hover:translate-x-1 rtl:-scale-x-100 rtl:group-hover:-translate-x-1" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<hr class="m-0 h-px border-none bg-[#eee] dark:bg-line" aria-hidden="true" />
