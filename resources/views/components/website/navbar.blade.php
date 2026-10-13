{{--
    Main navbar + mobile drawer (the design's .navbar / .mobile-nav).
    transparent: over a full-screen hero (home, about): see-through, dark glass once scrolled.
    Otherwise (inner pages): dark glass from the start and the current page's link is highlighted.
--}}
@props(['transparent' => false, 'scrollThreshold' => 40])

@php
    $phone = app(\Modules\GeneralSettings\Settings\BusinessInfoSettings::class)->hotline;
    $links = [
        ['route' => 'home', 'label' => __('Home')],
        ['route' => 'properties', 'label' => __('Properties')],
        ['route' => 'projects', 'label' => __('Projects')],
        ['route' => 'developers', 'label' => __('Developers')],
    ];
    $isArabic = app()->getLocale() === 'ar';
    $tooltip = 'after:pointer-events-none after:absolute after:left-1/2 after:top-[calc(100%+8px)] after:-translate-x-1/2 after:-translate-y-1 after:rounded-md after:bg-[rgba(13,13,13,0.88)] after:px-[9px] after:py-1 after:text-[11px] after:font-medium after:whitespace-nowrap after:tracking-[0.02em] after:text-white after:opacity-0 after:transition-[opacity,transform] after:duration-180 after:content-[attr(data-tooltip)] hover:after:translate-y-0 hover:after:opacity-100';
    $iconButton = 'relative inline-flex size-[31px] shrink-0 cursor-pointer items-center justify-center rounded-full border border-white/18 bg-transparent text-white/88';
@endphp

<div x-data="{ open: false, scrolled: false }" x-effect="document.body.style.overflow = open ? 'hidden' : ''">
    <nav
        @if ($transparent)
            x-init="scrolled = scrollY > {{ $scrollThreshold }}"
            @scroll.window.passive="scrolled = scrollY > {{ $scrollThreshold }}"
            :class="{
                'bg-[rgba(8,8,8,0.82)] backdrop-blur-[48px] backdrop-saturate-180 border-white/6 dark:bg-[rgba(13,11,8,0.92)] dark:border-[rgba(237,232,223,0.06)]': scrolled,
                'bg-transparent border-transparent': ! scrolled,
            }"
        @endif
        @class([
            'fixed inset-x-0 top-0 z-200 flex h-nav items-center justify-between border-b px-12 [transition:background_0.5s_cubic-bezier(0.16,1,0.3,1),border-color_0.5s_ease,box-shadow_0.5s_ease] max-md:px-5',
            'border-white/6 bg-[rgba(8,8,8,0.88)] backdrop-blur-[48px] backdrop-saturate-180' => ! $transparent,
            'border-transparent bg-transparent' => $transparent,
        ])
        aria-label="{{ __('Main navigation') }}"
    >
        <a href="{{ route('home') }}" class="flex shrink-0 items-center" aria-label="{{ __('Akar Town — Home') }}">
            {{-- Smaller on phones (not in the design): at 230px the menu button didn't fit on screens under ~490px. --}}
            <x-website.logo width="230" class="max-w-full max-sm:w-[110px]" />
        </a>

        <div class="flex items-center gap-[15px] max-sm:gap-[10px]">
            <ul class="flex list-none items-center gap-[15px] max-md:hidden" role="list">
                @foreach ($links as $link)
                    <li>
                        <a
                            href="{{ route($link['route']) }}"
                            @class([
                                'relative font-sans text-[12px] font-semibold tracking-[0.18em] uppercase transition-colors duration-250 after:absolute after:-bottom-[5px] after:left-0 after:h-px after:bg-brand after:transition-[width] after:duration-380 after:ease-smooth hover:text-white/95 hover:after:w-full rtl:font-arabic rtl:tracking-[0.02em] rtl:normal-case',
                                'text-white after:w-full' => ! $transparent && request()->routeIs($link['route']),
                                'text-white/90 after:w-0' => $transparent || ! request()->routeIs($link['route']),
                            ])
                        >{{ $link['label'] }}</a>
                    </li>
                @endforeach
            </ul>

            @if ($phone)
                <a
                    href="tel:{{ $phone }}"
                    class="inline-flex shrink-0 items-center gap-[7px] text-[12px] font-semibold tracking-[0.06em] whitespace-nowrap text-white/90 transition-colors duration-250 hover:text-white max-md:hidden"
                    aria-label="{{ __('Call :phone', ['phone' => $phone]) }}"
                >
                    <x-website.icons.phone class="shrink-0 opacity-75" />
                    <span>{{ $phone }}</span>
                </a>
            @endif

            <div class="h-[18px] w-px shrink-0 bg-white/15 max-md:hidden" aria-hidden="true"></div>

            <div class="flex items-center max-md:hidden">
                <button
                    type="button"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-white/20 bg-[linear-gradient(135deg,rgba(215,20,52,0.96)_0%,rgb(185,10,36)_100%)] px-5 py-[9px] font-sans text-[11px] leading-none font-[650] tracking-[0.06em] whitespace-nowrap text-white backdrop-blur-[20px] transition-all duration-300 ease-out-ex hover:-translate-y-0.5 hover:bg-[linear-gradient(135deg,rgba(225,25,58,0.98)_0%,rgb(200,16,46)_100%)] rtl:font-arabic"
                >{{ __('Submit Property') }}</button>
            </div>

            {{-- Language switch: the knob slides to the chosen language, then the page reloads in it. --}}
            <a
                href="{{ route('locale.switch', $isArabic ? 'en' : 'ar') }}"
                x-data="{ arabic: @js($isArabic) }"
                @click.prevent="arabic = ! arabic; setTimeout(() => window.location = $el.href, 250)"
                dir="ltr"
                role="switch"
                :aria-checked="arabic"
                class="relative flex h-[31px] w-20 shrink-0 cursor-pointer items-center rounded-full border border-[rgba(160,50,72,0.45)] bg-[rgba(160,50,72,0.18)] p-[3px] outline-none backdrop-blur-[10px] transition-[box-shadow,border-color] duration-300 hover:border-[rgba(160,50,72,0.7)] rtl:mr-3"
                aria-label="{{ __('Switch language') }}"
            >
                <span class="relative z-1 flex-1 text-center font-sans text-[11px] leading-none font-bold tracking-[0.05em] transition-colors duration-300 select-none" :class="arabic ? 'text-white/50' : 'text-white'">EN</span>
                <span class="absolute top-[3px] left-[3px] z-0 h-[calc(100%-6px)] w-[calc(50%-3px)] rounded-full bg-brand transition-transform duration-350 ease-spring" :class="arabic && 'translate-x-full'"></span>
                <span class="relative z-1 flex-1 text-center font-arabic text-[11px] leading-none font-bold tracking-[0.05em] transition-colors duration-300 select-none" :class="arabic ? 'text-white' : 'text-white/50'">عر</span>
            </a>

            <button
                type="button"
                class="{{ $iconButton }} {{ $tooltip }} font-[inherit] transition-[background,border-color,color] duration-250 hover:border-[#c8102e] hover:bg-[rgba(200,16,46,0.85)] hover:text-white"
                aria-label="{{ __('Sign in or register') }}"
                data-tooltip="{{ __('Sign in') }}"
            >
                <x-website.icons.user class="size-[14px] flex-none" />
            </button>

            <button
                type="button"
                @click="$store.theme.toggle()"
                :aria-pressed="$store.theme.dark"
                :aria-label="$store.theme.dark ? @js(__('Switch to light mode')) : @js(__('Switch to dark mode'))"
                class="{{ $iconButton }} {{ $tooltip }} transition-[background,border-color,color,box-shadow] duration-250 hover:border-white/38 hover:bg-white/10 dark:border-[rgba(201,168,76,0.32)] dark:text-[rgba(237,232,223,0.88)] dark:shadow-[0_0_12px_rgba(201,168,76,0.08)] dark:hover:border-[rgba(201,168,76,0.55)] dark:hover:bg-[rgba(201,168,76,0.1)] dark:hover:shadow-[0_0_20px_rgba(201,168,76,0.15)]"
                data-tooltip="{{ __('Theme') }}"
            >
                <span class="absolute flex scale-60 rotate-90 items-center justify-center opacity-0 [transition:opacity_0.3s_ease,transform_0.4s_cubic-bezier(0.34,1.56,0.64,1)] dark:scale-100 dark:rotate-0 dark:opacity-100" aria-hidden="true">
                    <x-website.icons.sun />
                </span>
                <span class="absolute flex scale-100 rotate-0 items-center justify-center opacity-100 [transition:opacity_0.3s_ease,transform_0.4s_cubic-bezier(0.34,1.56,0.64,1)] dark:scale-60 dark:-rotate-90 dark:opacity-0" aria-hidden="true">
                    <x-website.icons.moon />
                </span>
            </button>
        </div>

        <button
            type="button"
            @click="open = ! open"
            :aria-expanded="open"
            class="z-10 hidden size-9 shrink-0 cursor-pointer flex-col justify-center gap-[5px] p-1.5 max-md:flex"
            aria-label="{{ __('Toggle navigation') }}"
        >
            <span class="pointer-events-none block h-[1.5px] w-[22px] rounded-[2px] bg-white transition-[transform,opacity] duration-300" :class="open && 'translate-y-[6.5px] rotate-45'"></span>
            <span class="pointer-events-none block h-[1.5px] w-[22px] rounded-[2px] bg-white transition-[transform,opacity] duration-300" :class="open && 'opacity-0'"></span>
            <span class="pointer-events-none block h-[1.5px] w-[22px] rounded-[2px] bg-white transition-[transform,opacity] duration-300" :class="open && '-translate-y-[6.5px] -rotate-45'"></span>
        </button>
    </nav>

    {{-- Mobile drawer: slides in from the end side. --}}
    <div
        class="pointer-events-none fixed inset-0 z-199 flex flex-col overflow-y-auto bg-[rgba(8,8,8,0.97)] px-8 pt-[calc(var(--spacing-nav)+24px)] pb-12 backdrop-blur-[20px] transition-transform duration-380 ease-drawer ltr:translate-x-full rtl:-translate-x-full"
        :class="{ 'pointer-events-auto translate-x-0': open, 'pointer-events-none ltr:translate-x-full rtl:-translate-x-full': ! open }"
        :aria-hidden="! open"
    >
        <ul class="mb-8 flex list-none flex-col">
            @foreach ($links as $link)
                <li>
                    <a
                        href="{{ route($link['route']) }}"
                        @click="open = false"
                        @class([
                            'block border-b border-white/7 py-4 font-sans text-[28px] font-light tracking-[-0.01em] transition-colors duration-200 hover:text-brand dark:hover:text-ink rtl:font-arabic',
                            'text-brand dark:text-ink' => request()->routeIs($link['route']),
                            'text-white/85 dark:text-ink-60' => ! request()->routeIs($link['route']),
                        ])
                    >{{ $link['label'] }}</a>
                </li>
            @endforeach
        </ul>
        <div class="mt-auto">
            <button
                type="button"
                class="w-full cursor-pointer rounded-full bg-brand px-6 py-[15px] text-[13px] font-semibold tracking-[0.1em] text-white uppercase transition-opacity duration-200 hover:opacity-85"
            >{{ __('Submit Property') }}</button>
            @if ($phone)
                <a
                    href="tel:{{ $phone }}"
                    @click="open = false"
                    class="mt-4 inline-flex items-center gap-2 text-[13px] font-semibold tracking-[0.06em] text-white/75 transition-colors duration-200 hover:text-white"
                    aria-label="{{ __('Call :phone', ['phone' => $phone]) }}"
                >
                    <x-website.icons.phone class="opacity-70" />
                    {{ $phone }}
                </a>
            @endif
        </div>
    </div>
</div>
