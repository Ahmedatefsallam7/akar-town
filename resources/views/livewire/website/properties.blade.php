{{--
    Properties listing (the design's listings.html below the page header): sticky filter bar, advanced filters
    drawer, results bar (count, sort, grid / list) and the cards with "Load More".
--}}
@php
    use App\Support\Website\SearchOptions;

    $count = fn (int $n, string $one, string $many) => trans_choice($one.'|'.$many, $n, ['count' => $n]);
    $beds = collect(range(1, 5))->mapWithKeys(fn ($n) => [(string) $n => $count($n, ':count Bed', ':count Beds')])->put('6+', __('6+ Beds'))->all();
    $baths = collect(range(1, 4))->mapWithKeys(fn ($n) => [(string) $n => $count($n, ':count Bath', ':count Baths')])->put('5+', __('5+ Baths'))->all();
    $prices = ['0-2000000' => __('Under 2M EGP'), '2000000-5000000' => __('2M – 5M EGP'), '5000000-10000000' => __('5M – 10M EGP'), '10000000-20000000' => __('10M – 20M EGP'), '20000000-' => __('20M+ EGP')];
    $statuses = ['for-sale' => __('For Sale'), 'for-rent' => __('For Rent'), 'off-plan' => __('Off Plan')];
    $pad = 'px-12 max-xl:px-7 max-md:px-5';
    $clear = 'rounded-full border leading-[normal] border-transparent px-[14px] py-[7px] text-[11px] font-medium tracking-[0.1em] text-ink-30 uppercase transition-[color,border-color] duration-200 hover:border-[rgba(200,16,46,0.2)] hover:text-danger rtl:font-arabic';
    $apply = 'inline-flex cursor-pointer leading-[normal] items-center gap-2 rounded-full border border-white/20 bg-[linear-gradient(135deg,rgba(215,20,52,0.96)_0%,rgb(185,10,36)_100%)] px-5 py-[9px] text-[12px] font-semibold tracking-[0.04em] text-white transition-all duration-300 ease-out-ex hover:-translate-y-0.5 hover:bg-[linear-gradient(135deg,rgba(225,25,58,0.98)_0%,rgb(200,16,46)_100%)] rtl:font-arabic';
    $groupLabel = 'mb-[14px] flex items-center gap-2 text-[10px] font-semibold tracking-[0.16em] text-black/40 uppercase dark:text-white/45 rtl:font-arabic';
    $range = 'flex items-stretch overflow-hidden rounded-[10px] border border-black/12 bg-black/3 transition-[border-color] duration-220 dark:border-line-md dark:bg-surface-2';
    $rangeInput = 'min-w-0 flex-1 border-none leading-[normal] bg-transparent p-[10px] text-center text-[13px] text-ink outline-none placeholder:text-[12px] placeholder:text-black/28 dark:bg-transparent dark:placeholder:text-ink-30';
    $chip = 'flex cursor-pointer items-center gap-1.5 rounded-full border border-black/12 bg-transparent px-[14px] py-[7px] text-[12px] font-medium text-black/55 transition-all duration-200 ease-out-ex select-none hover:border-[rgba(200,16,46,0.5)] hover:bg-[rgba(200,16,46,0.06)] hover:text-ink has-checked:border-brand has-checked:bg-[rgba(200,16,46,0.08)] has-checked:font-semibold has-checked:text-ink dark:border-white/12 dark:text-white/60 dark:hover:bg-[rgba(200,16,46,0.1)] dark:hover:text-white dark:has-checked:bg-[rgba(200,16,46,0.18)] dark:has-checked:text-white rtl:font-arabic';
    $chipDot = 'size-[7px] shrink-0 rounded-full border-[1.5px] border-black/25 transition-[background,border-color] duration-180 group-hover/chip:border-brand group-has-checked/chip:border-brand group-has-checked/chip:bg-brand dark:border-white/28';
@endphp

<div x-data="{ drawer: false, view: 'grid' }" x-effect="document.body.style.overflow = drawer ? 'hidden' : ''" @keydown.escape.window="drawer = false">
    {{-- Filter bar --}}
    <div class="sticky top-nav z-100 border-b border-ink-10 bg-page">
        <div class="{{ $pad }} mx-auto flex max-w-[1320px] flex-wrap items-center gap-2 py-[10px] max-md:px-4 max-md:py-3">
            <div class="relative min-w-[160px] flex-1 max-w-[240px] max-md:max-w-full max-md:basis-full">
                <svg class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-ink-30 rtl:right-3 rtl:left-auto" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" /></svg>
                <input
                    type="text"
                    wire:model.live.debounce.350ms="q"
                    placeholder="{{ __('Search location, project…') }}"
                    class="w-full rounded-full border border-ink-10 bg-transparent py-[9px] leading-[normal] pr-[14px] pl-9 text-[13px] text-ink transition-[border-color] duration-200 outline-none placeholder:text-ink-30 focus:border-brand dark:border-line-md dark:bg-surface-2 dark:placeholder:text-ink-30 rtl:py-[10px] rtl:pr-[38px] rtl:pl-3"
                />
            </div>

            <x-website.filter-select model="type" :label="__('Type')" :placeholder="__('All Types')" :options="SearchOptions::values('unittype')">
                <x-slot:icon><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" /><polyline points="9 22 9 12 15 12 15 22" /></svg></x-slot:icon>
            </x-website.filter-select>
            <x-website.filter-select model="price" :label="__('Price')" :placeholder="__('Any Price')" :options="$prices">
                <x-slot:icon><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23" /><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" /></svg></x-slot:icon>
            </x-website.filter-select>
            <x-website.filter-select model="beds" :label="__('Beds')" :placeholder="__('Any Beds')" :options="$beds">
                <x-slot:icon><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M2 9V4a1 1 0 0 1 1-1h18a1 1 0 0 1 1 1v5" /><path d="M2 20v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5" /><path d="M2 20h20" /><path d="M2 9h20" /><rect x="7" y="9" width="4" height="4" rx="1" /></svg></x-slot:icon>
            </x-website.filter-select>
            <x-website.filter-select model="baths" :label="__('Baths')" :placeholder="__('Any Baths')" :options="$baths">
                <x-slot:icon><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 6 6.5 3.5a1.5 1.5 0 0 0-1-.5C4.683 3 4 3.683 4 4.5V17a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5" /><line x1="10" y1="5" x2="8" y2="7" /><line x1="2" y1="12" x2="22" y2="12" /><line x1="7" y1="19" x2="7" y2="21" /><line x1="17" y1="19" x2="17" y2="21" /></svg></x-slot:icon>
            </x-website.filter-select>

            <div class="mx-1 h-7 w-px shrink-0 bg-ink-10"></div>

            <button
                type="button"
                @click="drawer = true"
                class="flex shrink-0 cursor-pointer items-center gap-2 rounded-full border py-1.5 pr-3 pl-[7px] whitespace-nowrap transition-[border-color,background] duration-220 hover:border-[rgba(200,16,46,0.4)] hover:bg-[rgba(200,16,46,0.06)]"
                :class="drawer ? 'border-[rgba(200,16,46,0.4)] bg-[rgba(200,16,46,0.06)]' : 'border-ink-10 bg-transparent'"
            >
                <span class="grid size-7 shrink-0 place-items-center rounded-full text-brand transition-[background] duration-220" :class="drawer ? 'bg-[rgba(200,16,46,0.15)]' : 'bg-[rgba(200,16,46,0.08)]'">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><line x1="4" y1="6" x2="20" y2="6" /><line x1="8" y1="12" x2="16" y2="12" /><line x1="11" y1="18" x2="13" y2="18" /></svg>
                </span>
                <span class="flex flex-col items-start leading-[1.2]">
                    <span class="text-[9px] font-semibold tracking-[0.14em] text-ink-30 uppercase rtl:font-arabic">{{ __('Filter') }}</span>
                    <span class="text-[12px] font-semibold text-ink rtl:font-arabic">{{ __('Advanced') }}</span>
                </span>
                @if ($advancedCount)
                    <span class="grid h-[18px] min-w-[18px] place-items-center rounded-full bg-brand px-[5px] text-[10px] font-bold text-white">{{ $advancedCount }}</span>
                @endif
            </button>

            <div class="ml-auto flex shrink-0 items-center gap-2 rtl:mr-auto rtl:ml-0">
                <button type="button" wire:click="clear" class="{{ $clear }}">{{ __('Reset') }}</button>
                <button type="button" wire:click="apply" class="{{ $apply }}">{{ __('Apply Filters') }}</button>
            </div>
        </div>
    </div>

    {{-- Advanced filters drawer --}}
    <div class="fixed inset-0 z-1000" :class="drawer ? 'pointer-events-auto' : 'pointer-events-none'">
        <div class="absolute inset-0 cursor-pointer bg-[rgba(6,6,10,0.42)] transition-opacity duration-350" :class="drawer ? 'opacity-100' : 'opacity-0'" @click="drawer = false"></div>
        <aside
            class="absolute inset-y-0 right-0 flex w-[min(420px,calc(100vw-2.5rem))] translate-x-full flex-col overflow-hidden border-l border-black/9 bg-white transition-transform duration-400 ease-out-ex dark:border-white/8 dark:bg-[rgb(17,17,17)] max-md:w-screen"
            :class="{ 'translate-x-0': drawer, 'translate-x-full': ! drawer }"
            aria-label="{{ __('Advanced Filters') }}"
        >
            <span class="pointer-events-none absolute inset-y-0 left-0 w-0.5 bg-brand" aria-hidden="true"></span>
            <header class="flex flex-none items-start justify-between gap-4 border-b border-black/7 px-7 pt-7 pb-5 dark:border-white/8 max-md:px-5">
                <div>
                    <span class="mb-1.5 block text-[9px] font-bold tracking-[0.2em] text-brand uppercase dark:text-ink-60 rtl:font-arabic">{{ __('Refine') }}</span>
                    <h2 class="m-0 font-sans text-[clamp(1.25rem,2.5vw,1.6rem)] leading-[1.1] font-light tracking-[-0.02em] text-ink dark:text-white rtl:font-arabic">{{ __('Advanced Filters') }}</h2>
                </div>
                <button type="button" @click="drawer = false" class="grid size-9 shrink-0 cursor-pointer place-items-center rounded-full border border-black/12 bg-transparent text-black/40 transition-[background,color,border-color,rotate] duration-[220ms,220ms,220ms,350ms] hover:rotate-90 hover:border-[rgba(200,16,46,0.35)] hover:bg-[rgba(200,16,46,0.08)] hover:text-ink dark:border-white/14 dark:text-white/45 dark:hover:border-[rgba(200,16,46,0.4)] dark:hover:bg-[rgba(200,16,46,0.12)] dark:hover:text-white" aria-label="{{ __('Close advanced filters') }}">
                    <x-website.icons.close width="14" height="14" stroke-width="2.5" />
                </button>
            </header>

            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-7 py-6 [scrollbar-width:none] max-md:px-5 [&::-webkit-scrollbar]:hidden">
                @php($group = 'mb-[22px] border-b border-black/7 pb-[22px] last:mb-0 last:border-b-0 last:pb-0 dark:border-white/8')
                <div class="{{ $group }}">
                    <div class="{{ $groupLabel }}">{{ __('Area (m²)') }}</div>
                    <div class="{{ $range }}">
                        <input type="number" min="0" wire:model="areaMin" placeholder="{{ __('Min') }}" class="{{ $rangeInput }}" />
                        <span class="flex shrink-0 items-center border-x border-black/7 bg-black/3 px-2 text-[10px] text-black/28 dark:border-white/8 dark:bg-white/3 dark:text-white/25">—</span>
                        <input type="number" min="0" wire:model="areaMax" placeholder="{{ __('Max') }}" class="{{ $rangeInput }}" />
                    </div>
                </div>
                <div class="{{ $group }}">
                    <div class="{{ $groupLabel }}">{{ __('Floor') }}</div>
                    <div class="{{ $range }}">
                        <input type="number" wire:model="floorMin" placeholder="{{ __('Min') }}" class="{{ $rangeInput }}" />
                        <span class="flex shrink-0 items-center border-x border-black/7 bg-black/3 px-2 text-[10px] text-black/28 dark:border-white/8 dark:bg-white/3 dark:text-white/25">—</span>
                        <input type="number" wire:model="floorMax" placeholder="{{ __('Max') }}" class="{{ $rangeInput }}" />
                    </div>
                </div>
                <div class="{{ $group }}">
                    <div class="{{ $groupLabel }}">{{ __('Status') }}</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($statuses as $value => $statusLabel)
                            <label class="{{ $chip }} group/chip">
                                <input type="checkbox" wire:model="status" value="{{ $value }}" class="hidden" />
                                <span class="{{ $chipDot }}"></span>{{ $statusLabel }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="{{ $group }}">
                    <div class="{{ $groupLabel }}">{{ __('Amenities') }}</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach (SearchOptions::values('amenities') as $value => $amenity)
                            <label class="{{ $chip }} group/chip">
                                <input type="checkbox" wire:model="amenities" value="{{ $value }}" class="hidden" />
                                <span class="{{ $chipDot }}"></span>{{ $amenity }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <footer class="flex flex-none items-center justify-between gap-3 border-t border-black/7 bg-black/2 px-7 py-4 dark:border-white/8 dark:bg-black/20 max-md:px-5">
                <button type="button" wire:click="clear" @click="drawer = false" class="{{ $clear }} border-black/12! text-black/45! hover:border-[rgba(200,16,46,0.4)]! hover:text-ink! dark:border-white/10! dark:text-white/40! dark:hover:text-white!">{{ __('Reset') }}</button>
                <button type="button" wire:click="apply" @click="drawer = false" class="{{ $apply }}">{{ __('Apply Filters') }}</button>
            </footer>
        </aside>
    </div>

    {{-- Results bar --}}
    <div class="{{ $pad }} mx-auto flex max-w-[1320px] items-center justify-between gap-5 pt-6">
        <p class="text-[13px] text-ink-60">{!! __(':count properties found', ['count' => '<strong class="font-semibold text-ink">'.$total.'</strong>']) !!}</p>
        <div class="flex items-center gap-4">
            <x-website.filter-select model="sort" variant="sort" live :placeholder="__('Newest First')" :options="['price-asc' => __('Price: Low to High'), 'price-desc' => __('Price: High to Low'), 'area-desc' => __('Area: Large to Small')]" />
            <div class="flex overflow-hidden rounded-md border border-ink-10">
                @foreach (['grid' => [__('Grid view'), '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>'], 'list' => [__('List view'), '<rect x="3" y="4" width="18" height="3" rx="1"/><rect x="3" y="10.5" width="18" height="3" rx="1"/><rect x="3" y="17" width="18" height="3" rx="1"/>']] as $mode => [$title, $icon])
                    <button
                        type="button"
                        @click="view = @js($mode)"
                        title="{{ $title }}"
                        class="flex items-center border-none px-3 py-2 transition-all duration-200 hover:bg-ink hover:text-white dark:hover:bg-surface-3 dark:hover:text-ink"
                        :class="view === @js($mode) ? 'bg-ink text-white dark:bg-surface-3 dark:text-ink' : 'bg-white text-ink-30 dark:bg-surface-1 dark:text-ink-60'"
                    >
                        <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">{!! $icon !!}</svg>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Cards --}}
    <div class="{{ $pad }} mx-auto max-w-[1320px] pt-7 {{ $more ? 'pb-0' : 'pb-20' }}">
        <div :data-view="view" class="group/grid grid grid-cols-3 gap-7 max-xl:grid-cols-2 max-md:grid-cols-1 data-[view=list]:grid-cols-1!">
            @foreach ($cards as $i => $card)
                <x-website.property-card :card="$card" :index="$i" wire:key="unit-{{ $card['id'] }}" />
            @endforeach
        </div>
    </div>

    @if ($more)
        <x-website.load-more>{{ __('Load More Properties') }}</x-website.load-more>
    @endif
</div>
