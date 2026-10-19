{{--
    A wishlist card (the design's .wl-card): photo with the remove button and status, then developer (projects) or
    type (properties), title, place, the key figures, price and "View". Inside the wishlist's Alpine scope (remove()).
    @var array $item  PropertyCards::from() or ProjectCards::from() item
    @var string $fav  the item's favourites id as JS (e.g. "12" or "'project-3'")
--}}
@props(['item', 'fav', 'project' => false])

@php
    // The design's status colours: off-plan gold, under construction orange, ready green, rent dark, sale red.
    $styles = ['offplan' => 'bg-[rgba(180,130,30,0.88)]', 'construction' => 'bg-[rgba(200,110,20,0.88)]', 'ready' => 'bg-[rgba(25,140,65,0.88)]', 'red' => 'bg-[rgba(180,130,30,0.88)]', 'ink' => 'bg-[rgba(13,13,13,0.75)]', 'green' => 'bg-[rgba(200,16,46,0.88)]'];
    $status = $project ? ($item['status'] ? [$item['status'], $styles[$item['statusStyle']] ?? $styles['offplan']] : null) : ($item['status'] ? [$item['status']['label'], $styles[$item['status']['style']] ?? $styles['green']] : null);
    $specs = $project
        ? array_filter([[$item['units'], __('Units')], $item['delivery'] ? [$item['delivery'], __('Delivery')] : null])
        : array_filter([$item['beds'] ? [$item['beds'], __('Beds')] : null, $item['baths'] !== null ? [$item['baths'], __('Baths')] : null, $item['area'] !== null ? [number_format($item['area']), __('m²')] : null]);
    $price = $project ? $item['price'] : ($item['price'] ? __('EGP :price', ['price' => $item['price']]) : null);
    $arrow = '<svg viewBox="0 0 16 14" aria-hidden="true" class="size-[11px] flex-none fill-none stroke-current stroke-2 rtl:-scale-x-100"><path d="M8 0.414C8 1.11 8.69 2.152 9.387 3.027C10.286 4.154 11.357 5.138 12.587 5.889C13.509 6.452 14.628 6.992 15.526 6.992M8 13.586C8 12.889 8.69 11.848 9.387 10.973C10.286 9.846 11.357 8.862 12.587 8.111C13.509 7.548 14.628 7.008 15.526 7.008M15.526 6.999H0.474" stroke-linecap="round" stroke-linejoin="round" /></svg>';
@endphp

<article
    x-show="$store.favourites.has({{ $fav }})"
    :class="removing.includes({{ $fav }}) && 'animate-card-out'"
    class="group flex flex-col overflow-hidden rounded-2xl border border-ink-10 bg-paper transition-[transform,box-shadow,border-color] duration-300 ease-out-ex hover:border-ink-30 dark:border-line dark:bg-surface-2 dark:hover:border-line-md dark:hover:shadow-[0_20px_48px_rgba(0,0,0,0.4)]"
>
    <div class="relative aspect-[16/10] shrink-0 overflow-hidden bg-ink-10">
        <img src="{{ $item['image'] ?? \App\Support\Website\Image::placeholder() }}" alt="{{ $item['title'] }}" loading="lazy" class="size-full object-cover transition-transform duration-600 ease-smooth group-hover:scale-104" />
        <button
            type="button"
            @click="remove({{ $fav }})"
            class="absolute top-[14px] right-[14px] z-2 flex size-[34px] cursor-pointer items-center justify-center rounded-full border-none bg-[rgba(200,16,46,0.9)] text-white backdrop-blur-[8px] transition-[background,scale] duration-200 hover:scale-110 hover:bg-[rgba(163,11,27,0.95)] rtl:right-auto rtl:left-[14px]"
            aria-label="{{ __('Remove from wishlist') }}"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true" class="pointer-events-none size-[14px] flex-none fill-current"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" /></svg>
        </button>
        @if ($status)
            <span class="absolute bottom-[14px] left-[14px] z-2 rounded px-[11px] py-[5px] text-[9px] font-bold tracking-[0.14em] text-white uppercase backdrop-blur-[10px] rtl:right-[14px] rtl:left-auto rtl:font-arabic rtl:tracking-normal {{ $status[1] }}">{{ $status[0] }}</span>
        @endif
    </div>

    <div class="flex flex-1 flex-col px-[22px] pt-5 pb-[22px]">
        @if ($project && $item['developer'])
            <div class="mb-1.5 font-sans text-[10px] tracking-[0.1em] text-ink-30 uppercase rtl:font-arabic rtl:tracking-normal">{{ $item['developer'] }}</div>
        @elseif (! $project && $item['type'])
            <div class="mb-2 text-[10px] font-bold tracking-[0.18em] text-brand uppercase rtl:font-arabic rtl:tracking-normal">{{ $item['type'] }}</div>
        @endif
        <h3 class="mb-2 line-clamp-2 text-[18px] leading-[1.25] font-semibold tracking-[-0.01em] text-ink rtl:font-arabic"><a href="{{ $item['url'] }}">{{ $item['title'] }}</a></h3>
        @if ($item['location'])
            <div class="mb-4 flex items-center gap-[5px] text-[12.5px] text-ink-60 rtl:font-arabic">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" class="size-3 shrink-0"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z" /><circle cx="12" cy="10" r="3" /></svg>
                {{ $item['location'] }}
            </div>
        @endif
        @if ($specs)
            <div class="mb-4 flex items-stretch overflow-hidden rounded-lg border border-ink-10 dark:border-line">
                @foreach ($specs as [$value, $label])
                    <div class="flex flex-1 flex-col items-center gap-[3px] border-r border-ink-10 px-1.5 py-2 last:border-r-0 dark:border-line rtl:border-r-0 rtl:border-l rtl:last:border-l-0">
                        <span class="font-sans text-[1.15rem] leading-none text-ink">{{ $value }}</span>
                        <span class="mt-0.5 text-[8px] tracking-[0.1em] text-ink-30 uppercase rtl:font-arabic rtl:tracking-normal">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        @endif
        <div class="mt-auto flex items-center justify-between gap-3">
            <div>
                @if ($price)
                    <div class="mb-1 text-[9px] font-medium tracking-[0.14em] text-ink-60 uppercase rtl:font-arabic rtl:tracking-normal">{{ __('Price') }}</div>
                    <div class="text-[17px] leading-none font-bold text-brand tabular-nums">{{ $price }}</div>
                @endif
            </div>
            <a href="{{ $item['url'] }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-brand px-5 py-[10px] text-[12px] leading-[normal] font-[650] tracking-[0.04em] whitespace-nowrap text-white transition-[background,translate] duration-250 hover:-translate-y-0.5 hover:bg-brand-lt rtl:font-arabic">
                {{ __('View') }} {!! $arrow !!}
            </a>
        </div>
    </div>
</article>
