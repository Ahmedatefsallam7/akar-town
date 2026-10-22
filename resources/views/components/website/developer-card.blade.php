{{--
    One developer card (the design's developers.html .dev-card): photo (or the initials) with the project count on the
    left, then name, short text, a call button and "View Developer". The whole card opens the developer's page; the
    photo panel stays on the left in Arabic too, as in the design.
    @var array $card  App\Support\Website\DeveloperCards::from()
--}}
@props(['card'])

<article
    x-data
    @click="window.location = @js($card['url'])"
    {{ $attributes->class('group/dev flex min-h-[180px] cursor-pointer flex-row overflow-hidden rounded-2xl border border-ink-10 bg-white transition-[translate,box-shadow,border-color] duration-350 ease-out-ex hover:border-[rgba(200,16,46,0.2)] rtl:flex-row-reverse dark:border-[rgba(237,232,223,0.07)] dark:bg-[#1c1912] dark:hover:border-[rgba(212,20,44,0.28)] dark:hover:shadow-[0_20px_60px_rgba(0,0,0,0.5)] max-[500px]:min-h-0 max-[500px]:flex-col max-[500px]:rtl:flex-col') }}
    aria-label="{{ $card['name'] }}"
>
    {{-- Photo panel --}}
    <div class="relative flex flex-[0_0_38%] items-center justify-center overflow-hidden border-r border-[rgba(200,16,46,0.1)] bg-[#0d0b08] rtl:border-r-0 rtl:border-l dark:border-[rgba(237,232,223,0.07)] max-[500px]:h-[130px] max-[500px]:flex-none max-[500px]:border-r-0 max-[500px]:border-b max-[500px]:rtl:border-l-0">
        <div class="pointer-events-none absolute inset-0 z-1 bg-[linear-gradient(to_top,rgba(0,0,0,0.55)_0%,rgba(0,0,0,0.1)_50%,transparent_100%)]"></div>
        @if ($card['image'])
            <img src="{{ $card['image'] }}" alt="{{ $card['name'] }}" loading="lazy" class="absolute inset-0 z-0 size-full object-cover transition-transform duration-650 ease-out-ex group-hover/dev:scale-107" />
        @else
            <span class="relative z-1 font-sans text-[clamp(3.8rem,5.5vw,5.2rem)] leading-none font-light tracking-[-0.02em] text-[rgba(200,16,46,0.22)] transition-colors duration-350 select-none group-hover/dev:text-[rgba(200,16,46,0.4)] dark:text-white/10 dark:group-hover/dev:text-white/22" aria-hidden="true">{{ $card['initials'] }}</span>
        @endif
        <div class="absolute inset-y-0 left-0 z-2 w-[3px] bg-brand rtl:right-0 rtl:left-auto max-[500px]:inset-x-0 max-[500px]:top-auto max-[500px]:h-[3px] max-[500px]:w-auto"></div>
        <span class="absolute top-3 left-3 z-2 rounded-full border border-white/25 bg-black/45 px-[9px] py-1 text-[9px] font-bold tracking-[0.14em] text-white uppercase backdrop-blur-[8px] rtl:font-arabic rtl:tracking-normal">{{ trans_choice(':count Project|:count Projects', $card['projects'], ['count' => $card['projects']]) }}</span>
    </div>

    {{-- Text --}}
    <div class="relative flex min-w-0 flex-1 flex-col overflow-hidden px-5 pt-5 pb-4">
        <span class="pointer-events-none absolute -right-2.5 -bottom-5 z-0 font-sans text-[clamp(5rem,9vw,8rem)] leading-none font-light tracking-[-0.04em] text-[rgba(13,13,13,0.045)] select-none dark:text-white/4 rtl:-left-2.5 rtl:right-auto" aria-hidden="true">{{ $card['initials'] }}</span>
        <h3 class="relative z-1 mb-[3px] line-clamp-1 font-sans text-[1.22rem] leading-[1.2] font-normal text-ink rtl:font-arabic">{{ $card['name'] }}</h3>
        @if ($card['description'])
            <p class="relative z-1 mb-[14px] line-clamp-2 text-[12px] leading-[1.65] text-ink-60">{{ $card['description'] }}</p>
        @endif

        <div class="relative z-1 mt-auto flex items-center justify-between gap-2 pt-[11px] rtl:flex-row-reverse">
            @if ($card['phone'])
                <a
                    href="tel:{{ preg_replace('/[^\d+]/', '', $card['phone']) }}"
                    @click.stop
                    data-tip="{{ __('Call') }}"
                    aria-label="{{ __('Call :name', ['name' => $card['name']]) }}"
                    class="relative flex size-[34px] items-center justify-center rounded-lg border border-ink-10 bg-white text-ink-60 transition-all duration-220 ease-out-ex after:pointer-events-none after:absolute after:bottom-[calc(100%+7px)] after:left-1/2 after:-translate-x-1/2 after:rounded after:bg-ink after:px-2 after:py-1 after:text-[10px] after:tracking-[0.08em] after:whitespace-nowrap after:text-white after:opacity-0 after:transition-opacity after:duration-180 after:content-[attr(data-tip)] hover:border-[#1a8a3a] hover:bg-[rgba(26,138,58,0.08)] hover:text-[#1a8a3a] hover:after:opacity-100 dark:border-[rgba(237,232,223,0.13)] dark:bg-[rgba(28,25,18,0.8)]"
                >
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.63A2 2 0 012 1h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.09a16 16 0 006 6l1.27-.73a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 15.5v1.42z" /></svg>
                </a>
            @else
                <span></span>
            @endif
            <a href="{{ $card['url'] }}" @click.stop class="group/link flex items-center gap-1.5 rounded-full border-[1.5px] border-white/65 bg-white/48 px-4 py-[7px] text-[11px] font-medium tracking-[0.04em] text-brand-dk shadow-[0_2px_16px_rgba(0,0,0,0.08),0_1px_4px_rgba(0,0,0,0.05)] backdrop-blur-[16px] backdrop-saturate-180 transition-all duration-280 ease-out-ex hover:border-[rgba(200,16,46,0.35)] hover:bg-[rgba(200,16,46,0.08)] dark:border-[rgba(237,232,223,0.13)] dark:bg-[rgba(28,25,18,0.8)] dark:text-ink dark:hover:bg-[rgba(40,35,24,0.9)] dark:hover:text-brand rtl:font-arabic rtl:tracking-normal">
                {{ __('View Developer') }}
                <svg width="14" height="10" viewBox="0 0 16 12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-250 ease-out-ex group-hover/link:translate-x-[3px] rtl:-scale-x-100 rtl:group-hover/link:-translate-x-[3px]" aria-hidden="true"><path d="M1 6h14M9 1l6 5-6 5" /></svg>
            </a>
        </div>
    </div>
</article>
