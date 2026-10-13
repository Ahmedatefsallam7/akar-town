{{--
    Property card (the design's listings .prop-card): photo with badges and price, details, specs and the actions
    (compare, favourite, gallery, call, WhatsApp, details). In a list (data-view="list" on a group/grid parent) the
    photo sits beside the details. Fades up when it scrolls into view.
    @var array $card  App\Support\Website\PropertyCards::from()
--}}
@props(['card', 'index' => 0])

@php
    $business = app(\Modules\GeneralSettings\Settings\BusinessInfoSettings::class);
    $action = 'relative flex size-[34px] items-center justify-center rounded-lg border transition-all duration-220 ease-out-ex after:pointer-events-none after:absolute after:bottom-[calc(100%+7px)] after:left-1/2 after:-translate-x-1/2 after:rounded after:bg-ink after:px-2 after:py-1 after:text-[10px] after:tracking-[0.08em] after:whitespace-nowrap after:text-white after:opacity-0 after:transition-opacity after:duration-180 after:content-[attr(data-tip)] hover:after:opacity-100';
    $idle = 'border-ink-10 bg-white text-ink-60 dark:border-line dark:bg-surface-1 dark:text-ink-60';
    $badge = 'inline-flex items-center gap-1 rounded px-[10px] py-1 text-[10px] leading-none font-bold tracking-[0.12em] uppercase rtl:font-arabic';
    $badgeStyles = ['gold' => 'bg-brand text-white', 'red' => 'bg-danger text-white', 'ink' => 'bg-[rgba(13,13,13,0.75)] text-white backdrop-blur-[8px]', 'green' => 'bg-[rgb(30,126,82)] text-white'];
    $compare = ['id' => $card['id'], 'title' => $card['title'], 'thumb' => $card['image']];
@endphp

<article
    x-data="{ shown: false }"
    x-intersect.once.threshold.10="setTimeout(() => shown = true, {{ ($index % 3) * 60 }})"
    @click="if (! $event.target.closest('a, button')) window.location = @js($card['url'])"
    class="group/card relative translate-y-7 cursor-pointer overflow-hidden rounded-[20px] border border-ink-10 bg-white opacity-0 [transition:opacity_0.65s_var(--ease-out-ex),translate_0.65s_var(--ease-out-ex),border-color_0.35s_var(--ease-out-ex),box-shadow_0.35s_var(--ease-out-ex)] hover:border-[rgba(200,16,46,0.2)] dark:border-line dark:bg-surface-2 dark:shadow-[0_2px_16px_rgba(0,0,0,0.35)] dark:hover:border-line-md dark:hover:shadow-[0_8px_32px_rgba(0,0,0,0.55),0_0_0_1px_rgba(212,20,44,0.18)] group-data-[view=list]/grid:grid group-data-[view=list]/grid:grid-cols-[340px_1fr] max-md:group-data-[view=list]/grid:grid-cols-1"
    :class="{ 'translate-y-0 opacity-100': shown, 'translate-y-7 opacity-0': ! shown }"
>
    <div class="relative aspect-[4/3] overflow-hidden group-data-[view=list]/grid:aspect-auto group-data-[view=list]/grid:h-full group-data-[view=list]/grid:min-h-[220px] max-md:group-data-[view=list]/grid:h-[220px]">
        <div class="absolute top-[14px] right-[14px] z-4 flex flex-col items-end gap-1.5 rtl:right-auto rtl:left-[14px] rtl:items-start">
            @if ($card['featured'])
                <span class="{{ $badge }} {{ $badgeStyles['gold'] }}">{{ __('Featured') }}</span>
            @endif
            @if ($card['type'])
                <span class="{{ $badge }} {{ $badgeStyles['ink'] }}">{{ $card['type'] }}</span>
            @endif
        </div>
        <div class="absolute inset-x-0 bottom-0 z-3 flex items-end justify-between bg-[linear-gradient(to_top,rgba(13,13,13,0.82)_0%,transparent_100%)] px-4 pt-8 pb-[14px] rtl:flex-row-reverse">
            <div>
                <div class="font-sans text-[22px] leading-none font-semibold text-white dark:text-ink max-[480px]:text-[18px]">{{ $card['price'] ? __('EGP :price', ['price' => $card['price']]) : '—' }}</div>
                <div class="mt-[3px] text-[10px] tracking-[0.1em] text-white/60 uppercase">{{ __('Total Price') }}</div>
            </div>
            @if ($card['status'])
                <span class="{{ $badge }} {{ $badgeStyles[$card['status']['style']] }}">{{ $card['status']['label'] }}</span>
            @endif
        </div>
        @if ($card['image'])
            <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}" loading="lazy" class="size-full object-cover transition-transform duration-650 ease-smooth group-hover/card:scale-105" />
        @endif
    </div>

    <div class="px-5 pt-[18px] pb-[14px] rtl:text-right group-data-[view=list]/grid:flex group-data-[view=list]/grid:flex-col group-data-[view=list]/grid:justify-between group-data-[view=list]/grid:px-6 group-data-[view=list]/grid:py-[22px]">
        <div class="mb-2 flex items-center gap-2 rtl:flex-row-reverse">
            @if ($card['type'])
                <span class="text-[10px] font-semibold tracking-[0.14em] text-brand-dk uppercase dark:text-ink-60 rtl:font-arabic">{{ $card['type'] }}</span>
                <span class="size-[3px] shrink-0 rounded-full bg-ink-30"></span>
            @endif
            <span class="text-[10px] text-ink-30">{{ $card['date'] }}</span>
        </div>
        <h3 class="mb-1.5 line-clamp-1 font-sans text-[19px] leading-[1.25] font-normal text-ink rtl:font-arabic">
            <a href="{{ $card['url'] }}">{{ $card['title'] }}</a>
        </h3>
        @if ($card['location'])
            <div class="mb-[14px] flex items-center gap-[5px] text-[12px] text-ink-60 rtl:flex-row-reverse rtl:justify-end">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="shrink-0 text-brand" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z" /><circle cx="12" cy="10" r="3" /></svg>
                {{ $card['location'] }}
            </div>
        @endif

        <div class="mb-3 flex items-stretch overflow-hidden rounded-lg border border-ink-10 dark:border-line rtl:flex-row-reverse">
            @foreach ([
                'beds' => [$card['beds'] === 0 ? __('Studio') : $card['beds'], __('Beds')],
                'baths' => [$card['baths'], __('Baths')],
                'area' => [$card['area'], __('m²')],
                'floor' => [$card['floor'], __('Floor')],
            ] as $key => [$value, $label])
                @continue($key === 'beds' && ! $card['beds'])
                <div class="flex flex-1 flex-col items-center gap-[3px] border-r border-ink-10 px-1.5 py-2 last:border-r-0 dark:border-line rtl:border-r-0 rtl:border-l rtl:last:border-l-0">
                    <span class="font-sans text-[1.15rem] leading-none font-normal text-ink">{{ $value ?? '—' }}</span>
                    <span class="mt-0.5 text-[8px] tracking-[0.1em] text-ink-30 uppercase">{{ $label }}</span>
                </div>
            @endforeach
        </div>

        <div class="flex items-center gap-2 py-0.5 rtl:flex-row-reverse">
            <button
                type="button"
                @click="$store.compare.toggle(@js($compare))"
                class="{{ $action }}"
                :class="$store.compare.has({{ $card['id'] }}) ? 'border-brand bg-[rgba(200,16,46,0.1)] text-brand-dk' : '{{ $idle }} hover:border-brand hover:bg-[rgba(200,16,46,0.08)] hover:text-brand dark:hover:bg-[rgba(212,20,44,0.08)]'"
                :data-tip="$store.compare.has({{ $card['id'] }}) ? @js(__('Remove from compare')) : @js(__('Add to compare'))"
                aria-label="{{ __('Compare') }}"
            >
                <x-website.icons.compare width="15" height="15" />
            </button>
            <button
                type="button"
                @click="$store.favourites.toggle({{ $card['id'] }})"
                class="{{ $action }}"
                :class="$store.favourites.has({{ $card['id'] }}) ? 'border-danger bg-[rgba(204,34,34,0.06)] text-danger' : '{{ $idle }} hover:border-brand hover:bg-[rgba(200,16,46,0.08)] hover:text-brand dark:hover:bg-[rgba(212,20,44,0.08)]'"
                :data-tip="$store.favourites.has({{ $card['id'] }}) ? @js(__('Remove from favorites')) : @js(__('Save to favorites'))"
                aria-label="{{ __('Favorite') }}"
            >
                <svg width="15" height="15" :fill="$store.favourites.has({{ $card['id'] }}) ? 'currentColor' : 'none'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z" /></svg>
            </button>
            @if ($card['images'])
                <button
                    type="button"
                    @click="$store.lightbox.show(@js($card['images']), 0)"
                    class="{{ $action }} {{ $idle }} hover:border-brand hover:bg-[rgba(200,16,46,0.08)] hover:text-brand dark:hover:bg-[rgba(212,20,44,0.08)]"
                    data-tip="{{ __('View gallery') }}"
                    aria-label="{{ __('Gallery') }}"
                >
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" /><circle cx="8.5" cy="8.5" r="1.5" /><polyline points="21,15 16,10 5,21" /></svg>
                </button>
            @endif
            @if ($business->hotline)
                <a
                    href="tel:{{ $business->hotline }}"
                    @click="$track('phone', { unit_id: {{ $card['id'] }} })"
                    class="{{ $action }} {{ $idle }} hover:border-[rgb(26,138,58)] hover:bg-[rgba(26,138,58,0.08)] hover:text-[rgb(26,138,58)]"
                    data-tip="{{ __('Call agent') }}"
                    aria-label="{{ __('Call agent') }}"
                >
                    <svg width="15" height="15" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" /></svg>
                </a>
            @endif
            @if ($business->whatsapp)
                <a
                    href="https://wa.me/{{ preg_replace('/\D/', '', $business->whatsapp) }}"
                    target="_blank"
                    rel="noopener"
                    @click="$track('whatsapp', { unit_id: {{ $card['id'] }} })"
                    class="{{ $action }} {{ $idle }} hover:border-[rgb(37,211,102)] hover:bg-[rgba(37,211,102,0.1)] hover:text-[rgb(37,211,102)]"
                    data-tip="{{ __('WhatsApp') }}"
                    aria-label="{{ __('WhatsApp') }}"
                >
                    <x-website.icons.whatsapp width="15" height="15" />
                </a>
            @endif
            <a
                href="{{ $card['url'] }}"
                class="group/detail ml-auto flex items-center gap-1.5 rounded-full border-[1.5px] border-white/65 bg-white/48 px-[18px] py-2 text-[11px] font-medium tracking-[0.04em] text-brand-dk shadow-[0_2px_16px_rgba(0,0,0,0.08),0_1px_4px_rgba(0,0,0,0.05)] backdrop-blur-[16px] backdrop-saturate-180 transition-all duration-280 ease-out-ex dark:border-[rgba(237,232,223,0.14)] dark:bg-[rgba(237,232,223,0.06)] dark:text-ink dark:hover:border-[rgba(212,20,44,0.28)] dark:hover:bg-[rgba(212,20,44,0.12)]"
            >
                <span>{{ __('Details') }}</span>
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true" class="transition-transform group-hover/detail:translate-x-[3px] rtl:-scale-x-100"><polyline points="9,18 15,12 9,6" /></svg>
            </a>
        </div>
    </div>
</article>
