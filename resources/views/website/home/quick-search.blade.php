{{--
    Quick property search (the design's .qf): Buy / Rent / New Projects with their sub-tabs, a text search and
    filter chips whose panels open below them (teleported to <body> as in the design; unlike the design they stay
    open while the page scrolls, following their chip, and long lists scroll). Sends a GET search to the
    properties (or projects) page. Lists are sent with numbered names (type[0]=…): Livewire rewrites a bare "type[]"
    into "type." in the address, which a reload or a shared link then loses.
--}}
@php
    use App\Support\Website\SearchOptions;

    $tabs = [
        'buy' => ['label' => __('Buy'), 'subs' => ['all' => __('All'), 'primary' => __('Primary'), 'resale' => __('Resale')]],
        'rent' => ['label' => __('Rent'), 'subs' => ['all' => __('All'), 'short' => __('Short Term'), 'long' => __('Long Term')]],
        'new' => ['label' => __('New Projects'), 'subs' => ['all' => __('All'), 'off-plan' => __('Off-Plan')]],
    ];
    $filters = [
        ['name' => 'type', 'label' => __('Property Type'), 'options' => SearchOptions::values('unittype')],
        ['name' => 'beds', 'label' => __('Beds & Baths'), 'options' => ['0' => __('Studio'), '1' => __('1 Bedroom'), '2' => __('2 Bedrooms'), '3' => __('3 Bedrooms'), '4' => __('4 Bedrooms'), '5+' => __('5+ Bedrooms')]],
        // The design's range slider (0 → 50M+ EGP), not a list.
        ['name' => 'price', 'label' => __('Price (EGP)'), 'range' => true],
        ['name' => 'area', 'label' => __('Area (sqm)'), 'options' => ['0-80' => __('Under 80'), '80-120' => __('80 – 120'), '120-200' => __('120 – 200'), '200-300' => __('200 – 300'), '300-' => __('300+')]],
        ['name' => 'finishing', 'label' => __('Finishing'), 'options' => SearchOptions::values('finishingtype')],
    ];
@endphp

<section
    class="relative bg-page pt-[70px]"
    aria-label="{{ __('Quick property search') }}"
    x-data="{
        tab: 'buy',
        sub: { buy: 'all', rent: 'all', new: 'all' },
        open: null,
        anchor: null,
        // Price slider, in millions (50 = 50M+).
        priceLo: 0,
        priceHi: 50,
        get priceAny() { return this.priceLo === 0 && this.priceHi >= 50 },
        priceAmount(n) { return (n >= 50 ? @js(__(':countM+ EGP')) : @js(__(':countM EGP'))).replace(':count', n) },
        get priceText() { return this.priceAny ? @js(__('Any price')) : this.priceAmount(this.priceLo) + ' – ' + this.priceAmount(this.priceHi) },
        // What the properties page's price filter reads: one EGP range, e.g. 5000000-20000000, or 50000000- for 50M+.
        get priceValue() { return this.priceLo * 1e6 + '-' + (this.priceHi >= 50 ? '' : this.priceHi * 1e6) },
        priceMove(handle) {
            this.priceLo = Number(this.priceLo)
            this.priceHi = Number(this.priceHi)
            if (this.priceLo >= this.priceHi) handle === 'lo' ? (this.priceLo = this.priceHi - 1) : (this.priceHi = this.priceLo + 1)
        },
        pos: { top: 0, left: 0 },
        // The open list follows its chip while the page scrolls; it closes once the chip leaves the screen.
        place() {
            if (this.open === null) return
            const r = this.anchor.getBoundingClientRect()
            if (r.bottom < 0 || r.top > innerHeight) return (this.open = null)
            this.pos = { top: r.bottom + 8, left: r.left }
        },
    }"
    @click.window="open = null"
    @resize.window="place()"
    @scroll.window.passive="place()"
>
    <div class="px-[60px] max-[720px]:px-5">
        <form
            id="quick-search"
            method="GET"
            :action="tab === 'new' ? @js(route('projects')) : @js(route('properties'))"
            class="overflow-hidden rounded-[20px] border border-ink-10 bg-white dark:border-[rgba(237,232,223,0.18)] dark:bg-surface-1 dark:shadow-[0_4px_40px_rgba(0,0,0,0.55)]"
        >
            <input type="hidden" name="intent" :value="tab" />
            <input type="hidden" name="category" :value="sub[tab]" />

            {{-- Buy / Rent / New Projects --}}
            <div class="flex items-center gap-1 border-b border-ink-10 bg-page px-4 py-3 dark:border-[rgba(237,232,223,0.14)] dark:bg-surface-0 max-[480px]:gap-0.5 max-[480px]:px-3 max-[480px]:py-[10px]" role="tablist" aria-label="{{ __('Search intent') }}">
                @foreach ($tabs as $key => $tab)
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="tab === @js($key)"
                        @click="tab = @js($key)"
                        class="cursor-pointer rounded-[99px] px-[22px] py-2 font-sans text-[13.5px] leading-none transition-[background,color] duration-180 rtl:font-arabic max-[480px]:px-[14px] max-[480px]:py-[7px] max-[480px]:text-[13px]"
                        :class="tab === @js($key)
                            ? 'bg-ink font-semibold text-page dark:text-surface-0'
                            : 'bg-transparent font-medium text-ink-60 hover:bg-ink-10 hover:text-ink dark:text-[rgba(237,232,223,0.55)] dark:hover:bg-surface-3 dark:hover:text-ink'"
                    >{{ $tab['label'] }}</button>
                @endforeach
            </div>

            {{-- Sub-tabs of the chosen intent --}}
            @foreach ($tabs as $key => $tab)
                <div class="px-6 pt-5 max-[480px]:px-4 max-[480px]:pt-4" role="tabpanel" x-show="tab === @js($key)" @if ($key !== 'buy') style="display: none" @endif>
                    <div class="flex gap-0 border-b border-ink-10 dark:border-[rgba(237,232,223,0.14)]" role="tablist" aria-label="{{ __(':intent category', ['intent' => $tab['label']]) }}">
                        @foreach ($tab['subs'] as $subKey => $subLabel)
                            <button
                                type="button"
                                role="tab"
                                @click="sub[@js($key)] = @js($subKey)"
                                class="-mb-px cursor-pointer border-b-2 bg-transparent px-[18px] pb-[14px] font-sans text-[13.5px] whitespace-nowrap transition-[color,border-color] duration-180 rtl:font-arabic"
                                :class="sub[@js($key)] === @js($subKey)
                                    ? 'border-brand font-semibold text-ink'
                                    : 'border-transparent font-normal text-ink-60 hover:text-ink dark:text-[rgba(237,232,223,0.5)] dark:hover:text-ink'"
                            >{{ $subLabel }}</button>
                        @endforeach
                    </div>
                </div>
            @endforeach

            {{-- Text search + Find --}}
            <div class="flex gap-[10px] px-6 pt-5 pb-[18px] max-[720px]:flex-col max-[480px]:px-4 max-[480px]:pt-4 max-[480px]:pb-[14px]">
                <label class="flex flex-1 items-center gap-[10px] rounded-[30px] border border-ink-10 bg-page px-4 py-[11px] transition-[border-color] duration-180 dark:border-[rgba(237,232,223,0.18)] dark:bg-surface-2">
                    <svg class="block shrink-0 text-ink-30 dark:text-[rgba(237,232,223,0.4)]" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <circle cx="6.5" cy="6.5" r="5" stroke="currentColor" stroke-width="1.5" />
                        <path d="M10.5 10.5L14 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    </svg>
                    <input
                        type="text"
                        name="q"
                        class="flex-1 border-none bg-transparent font-sans text-[14px] text-ink outline-none placeholder:text-ink-30 dark:bg-surface-2 rtl:font-arabic"
                        placeholder="{{ __('City, area or project name…') }}"
                        aria-label="{{ __('Search city, area or project') }}"
                    />
                </label>
                <button type="submit" class="group inline-flex shrink-0 cursor-pointer items-center gap-[10px] rounded-[30px] border-none bg-brand px-[26px] py-[11px] font-sans text-[13.5px] font-semibold whitespace-nowrap text-white transition-[background,gap] duration-200 hover:gap-[14px] hover:bg-brand-dk rtl:font-arabic max-[720px]:justify-center">
                    {{ __('Find Properties') }}
                    <x-website.icons.arrow class="rtl:-scale-x-100" />
                </button>
            </div>

            {{-- Filter chips --}}
            <div class="flex flex-wrap gap-2 px-6 pb-5 max-[480px]:px-4 max-[480px]:pt-[14px] max-[480px]:pb-[18px]">
                @foreach ($filters as $i => $filter)
                    <div>
                        <button
                            type="button"
                            @click.stop="
                                if (open === {{ $i }}) { open = null; return }
                                anchor = $el
                                open = {{ $i }}
                                place()
                            "
                            :aria-expanded="open === {{ $i }}"
                            class="inline-flex cursor-pointer items-center gap-[7px] rounded-[99px] border bg-transparent px-4 py-2 font-sans text-[13px] font-medium whitespace-nowrap transition-[border-color,color,background] duration-180 rtl:font-arabic"
                            :class="open === {{ $i }}
                                ? 'border-ink-30 text-ink dark:border-[rgba(237,232,223,0.45)] dark:bg-surface-3 dark:text-ink'
                                : 'border-ink-10 text-ink-60 hover:border-ink-30 hover:text-ink dark:border-[rgba(237,232,223,0.22)] dark:bg-surface-2 dark:text-[rgba(237,232,223,0.75)] dark:hover:border-[rgba(237,232,223,0.45)] dark:hover:bg-surface-3 dark:hover:text-ink'"
                        >
                            @if ($filter['range'] ?? false)
                                <span x-text="priceAny ? @js($filter['label']) : priceText">{{ $filter['label'] }}</span>
                            @else
                                {{ $filter['label'] }}
                            @endif
                            <svg class="block shrink-0 transition-[rotate] duration-220" :class="open === {{ $i }} ? 'rotate-180 text-ink' : 'text-ink-30 dark:text-[rgba(237,232,223,0.45)]'" width="10" height="6" viewBox="0 0 10 6" fill="none" aria-hidden="true">
                                <path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>

                        <template x-teleport="body">
                            <div
                                role="listbox"
                                @click.stop
                                :style="`top: ${pos.top}px; left: ${pos.left}px`"
                                class="fixed top-0 left-0 z-9999 max-h-[min(320px,calc(100dvh-24px))] min-w-[190px] overflow-y-auto overscroll-contain rounded-[14px] border border-ink-10 bg-page p-2 shadow-[0_10px_36px_rgba(0,0,0,0.1),0_2px_8px_rgba(0,0,0,0.06)] transition-[opacity,translate,scale] duration-180"
                                :class="open === {{ $i }} ? 'pointer-events-auto translate-y-0 scale-100 opacity-100' : 'pointer-events-none -translate-y-1.5 scale-98 opacity-0'"
                            >
                                @if ($filter['range'] ?? false)
                                    {{-- The design's price slider (index.html .qf__price-panel); sent only when narrowed. --}}
                                    <div class="w-[280px] px-[10px] pt-2 pb-[6px]">
                                        <div class="mb-[18px] text-center font-sans text-[13px] font-semibold text-brand rtl:font-arabic" x-text="priceText">{{ __('Any price') }}</div>
                                        <div dir="ltr" class="relative mt-2 mb-1 h-1 rounded bg-ink-10">
                                            <div class="pointer-events-none absolute top-0 h-full rounded bg-brand" :style="`left: ${priceLo / 50 * 100}%; width: ${(priceHi - priceLo) / 50 * 100}%`"></div>
                                            <input type="range" min="0" max="50" step="1" x-model.number="priceLo" @input="priceMove('lo')" class="pointer-events-none absolute top-1/2 left-0 m-0 h-1 w-full -translate-y-1/2 appearance-none bg-transparent outline-none [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:size-[18px] [&::-moz-range-thumb]:cursor-pointer [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-brand [&::-moz-range-thumb]:bg-white [&::-moz-range-thumb]:shadow-[0_1px_6px_rgba(0,0,0,0.18)] [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:size-[18px] [&::-webkit-slider-thumb]:cursor-pointer [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-brand [&::-webkit-slider-thumb]:bg-white [&::-webkit-slider-thumb]:shadow-[0_1px_6px_rgba(0,0,0,0.18)] [&::-webkit-slider-thumb]:transition-transform [&::-webkit-slider-thumb]:hover:scale-118" aria-label="{{ __('Minimum price') }}" />
                                            <input type="range" min="0" max="50" step="1" x-model.number="priceHi" @input="priceMove('hi')" class="pointer-events-none absolute top-1/2 left-0 m-0 h-1 w-full -translate-y-1/2 appearance-none bg-transparent outline-none [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:size-[18px] [&::-moz-range-thumb]:cursor-pointer [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-brand [&::-moz-range-thumb]:bg-white [&::-moz-range-thumb]:shadow-[0_1px_6px_rgba(0,0,0,0.18)] [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:size-[18px] [&::-webkit-slider-thumb]:cursor-pointer [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-brand [&::-webkit-slider-thumb]:bg-white [&::-webkit-slider-thumb]:shadow-[0_1px_6px_rgba(0,0,0,0.18)] [&::-webkit-slider-thumb]:transition-transform [&::-webkit-slider-thumb]:hover:scale-118" aria-label="{{ __('Maximum price') }}" />
                                        </div>
                                        <div dir="ltr" class="mt-3 flex justify-between font-sans text-[10px] text-ink-30 select-none">
                                            <span>0</span><span>10{{ __('M') }}</span><span>25{{ __('M') }}</span><span>50{{ __('M') }}+</span>
                                        </div>
                                        <input type="hidden" form="quick-search" name="price[0]" :value="priceValue" :disabled="priceAny" />
                                    </div>
                                @else
                                @foreach ($filter['options'] as $value => $optionLabel)
                                        <label class="flex w-full cursor-pointer items-center gap-[10px] rounded-[9px] px-3 py-[9px] text-start font-sans text-[13px] font-normal whitespace-nowrap text-ink-60 transition-[background,color] duration-140 hover:bg-ink-10 hover:text-ink rtl:font-arabic">
                                            <input type="checkbox" form="quick-search" name="{{ $filter['name'] }}[{{ $loop->index }}]" value="{{ $value }}" class="size-[15px] shrink-0 cursor-pointer accent-brand" />
                                            {{ $optionLabel }}
                                        </label>
                                    @endforeach
                                @endif
                            </div>
                        </template>
                    </div>
                @endforeach
            </div>
        </form>
    </div>
</section>
