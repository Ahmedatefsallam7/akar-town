{{--
    A project card (the design's .proj-card on projects.html): photo with status, featured, developer and price, then
    place, name, description, units / delivery, amenity tags and the actions. Fades up when scrolled into view.
    @var array $card  App\Support\Website\ProjectCards::from()
--}}
@props(['card', 'index' => 0])

@php
    $status = [
        'offplan' => 'bg-[rgba(200,16,46,0.88)] before:bg-[rgb(255,179,179)]',
        'construction' => 'bg-[rgba(255,152,0,0.9)] before:bg-[rgb(255,243,205)]',
        'ready' => 'bg-[rgba(22,163,74,0.9)] before:bg-[rgb(187,247,208)]',
    ][$card['statusStyle']];
    $stats = array_filter([
        __('Units') => $card['units'],
        __('Delivery') => $card['delivery'],
    ], fn ($value) => $value !== null);
@endphp

<article
    x-data="{ shown: false }"
    x-intersect.once.threshold.10="setTimeout(() => shown = true, {{ ($index % 3) * 60 }})"
    @click="window.location = @js($card['url'])"
    {{ $attributes->class('group/card flex translate-y-6 cursor-pointer flex-col overflow-hidden rounded-[18px] border border-ink-10 bg-white opacity-0 [transition:opacity_0.65s_var(--ease-out-ex),translate_0.65s_var(--ease-out-ex),border-color_0.35s,box-shadow_0.35s_var(--ease-out-ex)] hover:border-[rgba(200,16,46,0.15)] dark:border-[rgba(237,232,223,0.07)] dark:bg-[rgb(28,25,18)] dark:hover:border-[rgba(212,20,44,0.25)] dark:hover:shadow-[0_20px_60px_rgba(0,0,0,0.5)]') }}
    :class="{ 'translate-y-0 opacity-100': shown, 'translate-y-6 opacity-0': ! shown }"
>
    <div class="relative aspect-[16/10] shrink-0 overflow-hidden">
        @if ($card['image'])
            <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}" loading="lazy" class="size-full object-cover transition-transform duration-650 ease-out-ex group-hover/card:scale-106" />
        @else
            <div class="size-full bg-night"></div>
        @endif
        <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(0deg,rgba(8,8,8,0.72)_0%,rgba(8,8,8,0.1)_45%,transparent_70%)]"></div>

        @if ($card['status'])
            <span class="{{ $status }} absolute top-[14px] left-[14px] inline-flex items-center gap-[5px] rounded-full px-[10px] py-[5px] text-[10px] font-bold tracking-[0.1em] text-white uppercase backdrop-blur-[12px] before:block before:size-[5px] before:rounded-full rtl:right-[14px] rtl:left-auto rtl:font-arabic">{{ $card['status'] }}</span>
        @endif
        @if ($card['featured'])
            <span class="absolute top-[14px] right-[14px] rounded-full border border-white/30 bg-white/15 px-[10px] py-1 text-[9px] font-bold tracking-[0.14em] text-white uppercase backdrop-blur-[12px] rtl:right-auto rtl:left-[14px] rtl:font-arabic">{{ __('Featured') }}</span>
        @endif

        <div class="absolute inset-x-[14px] bottom-[14px] flex items-end justify-between gap-3">
            <span class="text-[10px] font-semibold tracking-[0.08em] text-white/65 uppercase rtl:font-arabic">{{ $card['developer'] }}</span>
            @if ($card['price'])
                <div class="text-right rtl:text-left">
                    <span class="block text-[9px] font-semibold tracking-[0.1em] text-white/50 uppercase rtl:font-arabic">{{ __('Starting from') }}</span>
                    <span class="text-[15px] leading-[1.2] font-bold text-white">{{ $card['price'] }}</span>
                </div>
            @endif
        </div>
    </div>

    <div class="flex flex-1 flex-col px-[22px] pt-5">
        @if ($card['location'])
            <div class="mb-1.5 flex items-center gap-[5px] text-[11px] font-medium text-ink-60">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 text-brand" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" /><circle cx="12" cy="10" r="3" /></svg>
                {{ $card['location'] }}
            </div>
        @endif
        <h2 class="mb-2 font-sans text-[1.35rem] leading-[1.2] font-semibold text-ink transition-colors duration-200 group-hover/card:text-brand rtl:font-arabic">{{ $card['title'] }}</h2>
        @if ($card['description'])
            <p class="mb-4 line-clamp-2 text-[13px] leading-[1.65] text-ink-60">{{ $card['description'] }}</p>
        @endif
    </div>

    @if ($stats)
        <div class="mx-[22px] mb-[14px] grid overflow-hidden rounded-lg border border-ink-10 dark:border-[rgba(237,232,223,0.07)]" style="grid-template-columns: repeat({{ count($stats) }}, 1fr)">
            @foreach ($stats as $label => $value)
                <div class="border-ink-10 px-1.5 py-2 text-center not-last:border-r rtl:not-last:border-r-0 rtl:not-last:border-l dark:border-[rgba(237,232,223,0.07)]">
                    <span class="block font-sans text-[1.15rem] leading-none font-normal text-ink">{{ $value }}</span>
                    <span class="mt-[3px] block text-[8px] tracking-[0.1em] text-ink-30 uppercase rtl:font-arabic">{{ $label }}</span>
                </div>
            @endforeach
        </div>
    @endif

    @if ($card['tags'])
        <div class="flex flex-wrap gap-[5px] px-[22px]">
            @foreach ($card['tags'] as $tag)
                <span class="rounded-full bg-ink-10 px-[9px] py-1 text-[10px] font-semibold tracking-[0.06em] text-ink-60 uppercase dark:bg-[rgba(237,232,223,0.07)] rtl:font-arabic">{{ $tag }}</span>
            @endforeach
            @if ($card['more'])
                <span class="rounded-full border border-ink-10 bg-transparent px-[9px] py-1 text-[10px] font-semibold tracking-[0.06em] text-ink-30 uppercase dark:bg-[rgba(237,232,223,0.07)] dark:text-ink-60 rtl:font-arabic">{{ __('+:count more', ['count' => $card['more']]) }}</span>
            @endif
        </div>
    @endif

    <div class="flex gap-2 px-[22px] pt-[14px] pb-5">
        {{-- Wishlist (the navbar's favourites), saved as "project-<id>"; pops when pressed, as in the design --}}
        <button
            type="button"
            x-data="{ pop: false }"
            @click.stop="$store.favourites.toggle(@js('project-'.$card['id'])); pop = true; setTimeout(() => pop = false, 200)"
            :aria-label="$store.favourites.has(@js('project-'.$card['id'])) ? @js(__('Remove from Wishlist')) : @js(__('Add to Wishlist'))"
            :aria-pressed="$store.favourites.has(@js('project-'.$card['id']))"
            data-tip="{{ __('Save to Wishlist') }}"
            class="relative flex size-[34px] shrink-0 cursor-pointer items-center justify-center rounded-lg border bg-white transition-all duration-220 ease-out-ex after:pointer-events-none after:absolute after:bottom-[calc(100%+7px)] after:left-1/2 after:-translate-x-1/2 after:rounded after:bg-ink after:px-2 after:py-1 after:text-[10px] after:tracking-[0.08em] after:whitespace-nowrap after:text-white after:opacity-0 after:transition-opacity after:duration-180 after:content-[attr(data-tip)] hover:border-danger hover:bg-[rgba(204,34,34,0.06)] hover:text-danger hover:after:opacity-100 dark:bg-[rgba(28,25,18,0.8)] dark:hover:bg-[rgba(204,34,34,0.12)]"
            :class="{
                'border-danger! bg-[rgba(204,34,34,0.06)]! text-danger! dark:bg-[rgba(204,34,34,0.12)]! [&_path]:fill-current': $store.favourites.has(@js('project-'.$card['id'])),
                'border-ink-10 text-ink-60 dark:border-[rgba(237,232,223,0.13)]': ! $store.favourites.has(@js('project-'.$card['id'])),
                'scale-130': pop,
            }"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" /></svg>
        </button>
        <a
            href="{{ $card['unitsUrl'] }}"
            @click.stop
            data-tip="{{ __('Browse Units') }}"
            aria-label="{{ __('Browse Units') }}"
            class="relative flex size-[34px] items-center justify-center rounded-lg border border-ink-10 bg-white text-ink-60 transition-all duration-220 ease-out-ex after:pointer-events-none after:absolute after:bottom-[calc(100%+7px)] after:left-1/2 after:-translate-x-1/2 after:rounded after:bg-ink after:px-2 after:py-1 after:text-[10px] after:tracking-[0.08em] after:whitespace-nowrap after:text-white after:opacity-0 after:transition-opacity after:duration-180 after:content-[attr(data-tip)] hover:border-brand hover:bg-[rgba(200,16,46,0.08)] hover:text-brand hover:after:opacity-100 dark:border-[rgba(237,232,223,0.13)] dark:bg-[rgba(28,25,18,0.8)] dark:hover:bg-[rgba(200,16,46,0.1)]"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" /><rect x="14" y="3" width="7" height="7" /><rect x="3" y="14" width="7" height="7" /><rect x="14" y="14" width="7" height="7" /></svg>
        </a>
        <a
            href="{{ $card['url'] }}"
            @click.stop
            class="group/details ms-auto flex items-center gap-1.5 leading-[normal] rounded-full border-[1.5px] border-white/65 bg-white/48 px-4 py-[7px] text-[11px] font-medium tracking-[0.04em] whitespace-nowrap text-brand-dk shadow-[0_2px_16px_rgba(0,0,0,0.08),0_1px_4px_rgba(0,0,0,0.05)] backdrop-blur-[16px] backdrop-saturate-180 transition-all duration-280 ease-out-ex hover:border-[rgba(200,16,46,0.35)] hover:bg-[rgba(200,16,46,0.08)] hover:shadow-[0_6px_24px_rgba(0,0,0,0.12),0_2px_6px_rgba(0,0,0,0.07)] dark:border-[rgba(237,232,223,0.13)] dark:bg-[rgba(28,25,18,0.8)] dark:text-ink dark:hover:border-[rgba(200,16,46,0.35)] dark:hover:bg-[rgba(40,35,24,0.9)] dark:hover:text-brand rtl:font-arabic"
        >
            {{ __('View Project') }}
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-250 ease-out-ex group-hover/details:translate-x-[3px] rtl:-scale-x-100 rtl:group-hover/details:-translate-x-[3px]" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12" /><polyline points="12 5 19 12 12 19" /></svg>
        </a>
    </div>
</article>
