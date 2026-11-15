{{--
    The navbar's search window (App\Livewire\Website\GlobalSearch, AKAR-47), in the site's window look. Before typing:
    the visitor's recent searches (this browser only), the most popular compounds and quick links. Then the results
    grouped by type, with tabs to show one type, the typed text marked in each name, a small photo (or an icon), a
    second line and a price or count. ↑ / ↓ move through the rows and Enter opens one; Escape, the × and a click
    outside close it. Opened by the window event "open-search".
--}}
@php
    $groups = [
        'compounds' => __('Compounds'),
        'properties' => __('Properties'),
        'projects' => __('Projects'),
        'developers' => __('Developers'),
        'locations' => __('Locations'),
    ];
    // One icon per type: the row's picture when it has no photo (compounds never do).
    $icons = [
        'compounds' => '<rect x="3" y="3" width="7" height="9" rx="1" /><rect x="14" y="3" width="7" height="5" rx="1" /><rect x="14" y="12" width="7" height="9" rx="1" /><rect x="3" y="16" width="7" height="5" rx="1" />',
        'properties' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" /><polyline points="9 22 9 12 15 12 15 22" />',
        'projects' => '<path d="M3 21h18" /><path d="M5 21V7l8-4v18" /><path d="M19 21V11l-6-4" /><path d="M9 9v.01M9 12v.01M9 15v.01M9 18v.01" />',
        'developers' => '<rect x="2" y="7" width="20" height="14" rx="2" /><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2" />',
        'locations' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" /><circle cx="12" cy="10" r="3" />',
    ];
    $icon = fn (string $type, string $class = 'size-4') => '<svg class="'.$class.'" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$type].'</svg>';
    // The typed text marked in a name (same letters, any case), the rest escaped.
    $mark = function (string $title) use ($term): string {
        $at = $term !== '' ? mb_stripos($title, $term) : false;
        if ($at === false) {
            return e($title);
        }

        return e(mb_substr($title, 0, $at)).'<mark class="rounded-[3px] bg-[rgba(200,16,46,0.12)] px-px text-brand dark:bg-[rgba(200,16,46,0.25)] dark:text-[#ff8a9b]">'.e(mb_substr($title, $at, mb_strlen($term))).'</mark>'.e(mb_substr($title, $at + mb_strlen($term)));
    };
    // The rows come in one after another (written out so Tailwind makes each delay).
    $delays = ['motion-safe:[animation-delay:0ms]', 'motion-safe:[animation-delay:20ms]', 'motion-safe:[animation-delay:40ms]', 'motion-safe:[animation-delay:60ms]', 'motion-safe:[animation-delay:80ms]', 'motion-safe:[animation-delay:100ms]', 'motion-safe:[animation-delay:120ms]', 'motion-safe:[animation-delay:140ms]'];
    $kbd = 'inline-flex h-5 min-w-5 items-center justify-center rounded-[5px] border border-b-2 border-black/12 bg-white px-1 font-sans text-[10px] font-semibold text-ink-60 dark:border-white/12 dark:bg-white/5';
    $label = 'flex items-center gap-2 px-3 pt-2.5 pb-2 text-[10px] font-bold tracking-[0.14em] text-ink-30 uppercase rtl:font-arabic rtl:tracking-normal';
    $chip = 'inline-flex max-w-full cursor-pointer items-center gap-1.5 rounded-full border border-black/8 bg-white px-3 py-1.5 text-[12.5px] font-medium text-ink transition-[border-color,background,color] duration-150 hover:border-[rgba(200,16,46,0.35)] hover:text-brand rtl:font-arabic dark:border-white/10 dark:bg-white/4 dark:text-[#ede8df]';
    $counts = array_map('count', $results);
    $n = 0;
@endphp

<div
    x-data="{
        open: false,
        active: -1,
        scope: 'all',
        recent: [],
        show() {
            this.open = true
            document.body.style.overflow = 'hidden'
            try { this.recent = JSON.parse(localStorage.getItem('akar.search.recent') || '[]') } catch (e) { this.recent = [] }
            if (! $wire.opened) $wire.set('opened', true)
            this.$nextTick(() => { this.$refs.input.focus(); this.$refs.input.select() })
        },
        close() {
            this.open = false
            this.active = -1
            document.body.style.overflow = ''
        },
        search(text) {
            $wire.set('q', text)
            this.$refs.input.focus()
        },
        remember() {
            const text = $wire.q.trim()
            if (text.length < 2) return
            this.recent = [text, ...this.recent.filter(item => item.toLowerCase() !== text.toLowerCase())].slice(0, 5)
            try { localStorage.setItem('akar.search.recent', JSON.stringify(this.recent)) } catch (e) {}
        },
        forget() {
            this.recent = []
            try { localStorage.removeItem('akar.search.recent') } catch (e) {}
        },
        rows() {
            return [...this.$refs.results.querySelectorAll('[data-result]')].filter(row => row.offsetParent !== null)
        },
        mark(index) {
            const rows = this.rows()
            this.active = index
            rows.forEach((row, i) => row.toggleAttribute('data-active', i === index))
            rows[index]?.scrollIntoView({ block: 'nearest' })
        },
        move(step) {
            const rows = this.rows()
            if (rows.length) this.mark((this.active + step + rows.length) % rows.length)
        },
        go() {
            const row = this.rows()[this.active] ?? this.rows()[0]
            row?.click()
        },
    }"
    @open-search.window="show()"
    @keydown.escape.window="open && close()"
    x-show="open"
    x-cloak
    x-transition:enter="transition-opacity duration-200 ease-out"
    x-transition:enter-start="opacity-0"
    x-transition:leave="transition-opacity duration-150 ease-out"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-9998 flex items-start justify-center bg-[rgba(8,8,8,0.55)] px-4 pt-[min(10vh,96px)] pb-4 backdrop-blur-[8px] backdrop-saturate-150 max-sm:p-0"
    @click.self="close()"
    role="dialog"
    aria-modal="true"
    aria-label="{{ __('Search') }}"
>
    <div
        x-show="open"
        x-transition:enter="transition-[opacity,transform] duration-220 ease-out-ex"
        x-transition:enter-start="opacity-0 [transform:translateY(-12px)_scale(0.98)]"
        class="relative flex max-h-[min(700px,calc(100dvh-min(10vh,96px)-16px))] w-full max-w-[720px] flex-col overflow-hidden rounded-[22px] bg-page shadow-[0_0_0_1px_rgba(0,0,0,0.06),0_40px_100px_-20px_rgba(0,0,0,0.45)] max-sm:h-dvh max-sm:max-h-dvh max-sm:rounded-none dark:bg-[#17150f] dark:shadow-[0_0_0_1px_rgba(255,255,255,0.08),0_40px_100px_-20px_rgba(0,0,0,0.7)]"
    >
        {{-- The brand line, and a soft glow behind the box --}}
        <span class="pointer-events-none absolute inset-x-0 top-0 h-[3px] bg-[linear-gradient(90deg,transparent,#c8102e_30%,#e0334f_70%,transparent)]" aria-hidden="true"></span>
        <span class="pointer-events-none absolute -top-24 left-1/2 h-40 w-[70%] -translate-x-1/2 rounded-full bg-[rgba(200,16,46,0.10)] blur-3xl dark:bg-[rgba(200,16,46,0.18)]" aria-hidden="true"></span>

        {{-- The box --}}
        <div class="relative flex shrink-0 items-center gap-3 px-5 pt-5 pb-4 max-sm:px-4 max-sm:pt-4">
            <label class="group/box flex h-[54px] min-w-0 flex-1 items-center gap-3 rounded-[16px] border border-black/8 bg-white px-4 shadow-[0_1px_2px_rgba(0,0,0,0.04)] transition-[border-color,box-shadow] duration-200 focus-within:border-[rgba(200,16,46,0.45)] focus-within:shadow-[0_0_0_4px_rgba(200,16,46,0.10)] dark:border-white/10 dark:bg-white/4">
                <svg class="shrink-0 text-ink-30 transition-colors duration-200 group-focus-within/box:text-brand" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" /></svg>
                <input
                    x-ref="input"
                    type="search"
                    wire:model.live.debounce.200ms="q"
                    @input="active = -1; scope = 'all'"
                    @keydown.down.prevent="move(1)"
                    @keydown.up.prevent="move(-1)"
                    @keydown.enter.prevent="remember(); go()"
                    maxlength="80"
                    autocomplete="off"
                    enterkeyhint="search"
                    placeholder="{{ __('Search a compound, property, project, developer or location…') }}"
                    aria-label="{{ __('Search') }}"
                    class="h-full min-w-0 flex-1 bg-transparent text-[15.5px] text-ink outline-none placeholder:text-ink-30 rtl:font-arabic dark:text-[#ede8df] [&::-webkit-search-cancel-button]:hidden"
                />
                <span wire:loading.delay wire:target="q" class="size-4 shrink-0 animate-spin rounded-full border-2 border-ink-10 border-t-brand" aria-hidden="true"></span>
                @if ($term !== '')
                    <button type="button" wire:loading.remove wire:target="q" @click="search('')" class="grid size-6 shrink-0 cursor-pointer place-items-center rounded-full bg-black/6 text-ink-60 transition-[background] duration-150 hover:bg-black/10 dark:bg-white/8 dark:text-white/60" aria-label="{{ __('Clear') }}">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
                    </button>
                @endif
            </label>
            <button type="button" @click="close()" class="flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-full border border-black/8 bg-white text-ink transition-[background,rotate,border-color,color] duration-200 hover:rotate-90 hover:border-[rgba(200,16,46,0.35)] hover:text-brand dark:border-white/10 dark:bg-white/4 dark:text-[rgba(237,232,223,0.8)]" aria-label="{{ __('Close') }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
            </button>
        </div>

        {{-- Tabs: every type found, with its number --}}
        @if ($found)
            <div class="relative flex shrink-0 gap-1.5 overflow-x-auto px-5 pb-3 [scrollbar-width:none] max-sm:px-4 [&::-webkit-scrollbar]:hidden" role="tablist">
                @foreach (['all' => __('All')] + array_filter($groups, fn ($key) => $counts[$key] > 0, ARRAY_FILTER_USE_KEY) as $key => $title)
                    <button
                        type="button"
                        role="tab"
                        wire:key="tab-{{ $key }}"
                        @click="scope = '{{ $key }}'; active = -1"
                        :aria-selected="scope === '{{ $key }}'"
                        class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-full border px-3 py-1.5 text-[12px] font-semibold whitespace-nowrap transition-[background,border-color,color] duration-150 rtl:font-arabic"
                        :class="scope === '{{ $key }}' ? 'border-ink bg-ink text-page dark:border-[#ede8df] dark:bg-[#ede8df] dark:text-[#17150f]' : 'border-black/8 bg-transparent text-ink-60 hover:border-black/20 hover:text-ink dark:border-white/10 dark:text-white/60 dark:hover:text-white'"
                    >
                        {{ $title }}
                        <span class="text-[10.5px] opacity-60">{{ $key === 'all' ? array_sum($counts) : $counts[$key] }}</span>
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Before typing, "no results" or the results (dimmed while new ones load) --}}
        <div
            x-ref="results"
            wire:loading.class="opacity-50"
            wire:target="q"
            class="relative flex-1 overflow-y-auto overscroll-contain border-t border-black/6 px-2.5 py-2.5 transition-opacity duration-150 dark:border-white/6 [&::-webkit-scrollbar]:w-1 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-black/15 dark:[&::-webkit-scrollbar-thumb]:bg-white/15"
            aria-live="polite"
        >
            @if (! $searched)
                {{-- Recent searches (this browser) --}}
                <div x-show="recent.length" x-cloak class="mb-2">
                    <p class="{{ $label }} justify-between">
                        {{ __('Recent searches') }}
                        <button type="button" @click="forget()" class="cursor-pointer text-[10px] font-semibold tracking-normal text-ink-30 normal-case transition-colors duration-150 hover:text-brand">{{ __('Clear') }}</button>
                    </p>
                    <div class="flex flex-wrap gap-2 px-3">
                        <template x-for="item in recent" :key="item">
                            <button type="button" @click="search(item)" class="{{ $chip }}">
                                <svg class="size-3.5 shrink-0 text-ink-30" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg>
                                <span class="truncate" x-text="item"></span>
                            </button>
                        </template>
                    </div>
                </div>

                @if ($popular)
                    <div class="mb-2">
                        <p class="{{ $label }}">{{ __('Popular compounds') }}</p>
                        <div class="flex flex-wrap gap-2 px-3">
                            @foreach ($popular as $compound)
                                <a href="{{ $compound['url'] }}" class="{{ $chip }}">
                                    <span class="text-brand">{!! $icon('compounds', 'size-3.5') !!}</span>
                                    <span class="truncate">{{ $compound['title'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <p class="{{ $label }} pt-3">{{ __('Browse') }}</p>
                <div class="grid grid-cols-4 gap-2 px-2 max-sm:grid-cols-2">
                    @foreach (['properties', 'projects', 'developers', 'locations'] as $type)
                        <a href="{{ route($type) }}" class="group relative flex items-center gap-3 overflow-hidden rounded-[14px] border border-black/7 bg-white px-3 py-3 text-[13px] font-semibold text-ink transition-[border-color,box-shadow,translate] duration-200 hover:-translate-y-0.5 hover:border-[rgba(200,16,46,0.3)] hover:shadow-[0_10px_24px_-12px_rgba(200,16,46,0.35)] rtl:font-arabic dark:border-white/8 dark:bg-white/3 dark:text-[#ede8df]">
                            <span class="grid size-9 shrink-0 place-items-center rounded-[10px] bg-[linear-gradient(135deg,rgba(200,16,46,0.12),rgba(200,16,46,0.04))] text-brand transition-[background,color] duration-200 group-hover:bg-brand group-hover:text-white">{!! $icon($type, 'size-[17px]') !!}</span>
                            {{ $groups[$type] }}
                        </a>
                    @endforeach
                </div>
                <p class="px-3 pt-5 pb-2 text-center text-[12px] text-ink-30 rtl:font-arabic">{{ __('Type at least two letters, e.g. a compound’s name.') }}</p>
            @elseif (! $found)
                <div class="flex flex-col items-center gap-3 px-3 py-12 text-center motion-safe:animate-fade-up" role="status">
                    <span class="grid size-16 place-items-center rounded-full bg-[linear-gradient(135deg,rgba(200,16,46,0.10),rgba(200,16,46,0.02))] text-brand">
                        <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" /><path d="M8 11h6" /></svg>
                    </span>
                    <p class="text-[15px] font-semibold text-ink rtl:font-arabic dark:text-[#ede8df]">{{ __('No results for “:q”', ['q' => $term]) }}</p>
                    <p class="max-w-[300px] text-[12.5px] leading-relaxed text-ink-30 rtl:font-arabic">{{ __('Try a compound, area, project or developer name.') }}</p>
                </div>
            @else
                @foreach ($groups as $key => $title)
                    @if ($results[$key])
                        <section x-show="scope === 'all' || scope === '{{ $key }}'" class="mb-1 last:mb-0" wire:key="group-{{ $key }}">
                            <h3 x-show="scope === 'all'" class="{{ $label }}">
                                <span class="text-brand">{!! $icon($key, 'size-3.5') !!}</span>
                                {{ $title }}
                                <span class="h-px flex-1 bg-black/6 dark:bg-white/6"></span>
                            </h3>
                            @foreach ($results[$key] as $item)
                                <a
                                    href="{{ $item['url'] }}"
                                    data-result
                                    @click="remember()"
                                    @mouseenter="mark(rows().indexOf($el))"
                                    wire:key="{{ $key }}-{{ $item['url'] }}"
                                    class="group flex items-center gap-3.5 rounded-[14px] px-2.5 py-2 transition-[background,box-shadow] duration-120 focus-visible:outline-none data-active:bg-white data-active:shadow-[0_0_0_1px_rgba(0,0,0,0.05),0_6px_18px_-8px_rgba(0,0,0,0.18)] motion-safe:animate-fade-up {{ $delays[min($n++, 7)] }} dark:data-active:bg-white/5 dark:data-active:shadow-[0_0_0_1px_rgba(255,255,255,0.06)]"
                                >
                                    @if ($item['image'])
                                        <img src="{{ $item['image'] }}" alt="" width="52" height="52" loading="lazy" decoding="async" class="size-[52px] shrink-0 rounded-[12px] bg-black/5 object-cover ring-1 ring-black/5 dark:bg-white/5 dark:ring-white/8" />
                                    @else
                                        <span class="grid size-[52px] shrink-0 place-items-center rounded-[12px] bg-[linear-gradient(135deg,rgba(200,16,46,0.14),rgba(200,16,46,0.04))] text-brand ring-1 ring-[rgba(200,16,46,0.10)]">{!! $icon($key, 'size-5') !!}</span>
                                    @endif
                                    <span class="flex min-w-0 flex-1 flex-col gap-1">
                                        <span class="truncate text-[14.5px] leading-tight font-semibold text-ink rtl:font-arabic dark:text-[#ede8df]">{!! $mark($item['title']) !!}</span>
                                        @if ($item['sub'])
                                            <span class="flex min-w-0 items-center gap-1 text-[12px] text-ink-30 rtl:font-arabic">
                                                @if ($key !== 'developers')
                                                    <svg class="size-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" /><circle cx="12" cy="10" r="3" /></svg>
                                                @endif
                                                <span class="truncate">{{ $item['sub'] }}</span>
                                            </span>
                                        @endif
                                    </span>
                                    @if ($item['badge'] ?? null)
                                        <span @class([
                                            'shrink-0 rounded-full px-2.5 py-1 text-[11.5px] font-semibold whitespace-nowrap max-[420px]:hidden',
                                            'bg-[rgba(200,16,46,0.08)] font-sans text-brand' => $key === 'properties',
                                            'bg-black/5 text-ink-60 rtl:font-arabic dark:bg-white/6 dark:text-white/60' => $key !== 'properties',
                                        ])>{{ $item['badge'] }}</span>
                                    @endif
                                    <svg class="size-4 shrink-0 text-brand opacity-0 transition-[opacity,translate] duration-150 group-data-active:opacity-100 ltr:group-data-active:translate-x-0.5 rtl:-scale-x-100 rtl:group-data-active:-translate-x-0.5 max-sm:hidden" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                                </a>
                            @endforeach
                        </section>
                    @endif
                @endforeach
            @endif
        </div>

        {{-- Keys (big screens) and every property matching the text --}}
        <div @class(['flex shrink-0 items-center justify-between gap-3 border-t border-black/6 bg-black/[0.015] px-5 py-3 text-[11.5px] text-ink-30 rtl:font-arabic dark:border-white/6 dark:bg-white/2', 'max-sm:hidden' => ! $found])>
            <span class="flex items-center gap-3.5 max-sm:hidden">
                <span class="flex items-center gap-1"><kbd class="{{ $kbd }}">↑</kbd><kbd class="{{ $kbd }}">↓</kbd> {{ __('to move') }}</span>
                <span class="flex items-center gap-1"><kbd class="{{ $kbd }}">↵</kbd> {{ __('to open') }}</span>
                <span class="flex items-center gap-1"><kbd class="{{ $kbd }}">Esc</kbd> {{ __('to close') }}</span>
            </span>
            @if ($found)
                <a href="{{ route('properties', ['q' => $term]) }}" @click="remember()" class="ms-auto inline-flex items-center gap-1.5 rounded-full bg-brand px-3.5 py-1.5 font-semibold text-white transition-[background,gap] duration-150 hover:gap-2.5 hover:bg-brand-dk">
                    {{ __('See all properties') }}
                    <svg class="size-3.5 rtl:-scale-x-100" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14" /><path d="m12 5 7 7-7 7" /></svg>
                </a>
            @endif
        </div>
    </div>
</div>
