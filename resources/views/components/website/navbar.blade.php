{{--
    Main navbar + mobile drawer (the design's .navbar / .mobile-nav).
    transparent: over a full-screen hero (home, about): see-through, dark glass once scrolled.
    Otherwise (inner pages): dark glass from the start and the current page's link is highlighted.
--}}
@props(['transparent' => false, 'scrollThreshold' => 40])

@php
    $phone = app(\Modules\GeneralSettings\Settings\BusinessInfoSettings::class)->hotline;
    // The signed-in website account (none for guests).
    $account = auth('website')->user();
    $links = [
        ['route' => 'home', 'label' => __('Home')],
        ['route' => 'properties', 'label' => __('Properties')],
        // The design marks Projects / Developers on their detail pages too (not Properties on a property's page).
        ['route' => 'projects', 'label' => __('Projects'), 'active' => ['projects', 'projects.show']],
        ['route' => 'developers', 'label' => __('Developers'), 'active' => ['developers', 'developers.show']],
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
            'fixed inset-x-0 top-0 z-200 flex h-nav items-center justify-between border-b px-12 max-xs:px-4! [transition:background_0.5s_cubic-bezier(0.16,1,0.3,1),border-color_0.5s_ease,box-shadow_0.5s_ease] max-md:px-5',
            'border-white/6 bg-[rgba(8,8,8,0.88)] backdrop-blur-[48px] backdrop-saturate-180' => ! $transparent,
            'border-transparent bg-transparent' => $transparent,
        ])
        aria-label="{{ __('Main navigation') }}"
    >
        <a href="{{ route('home') }}" class="flex shrink-0 items-center" aria-label="{{ __('Akar Town — Home') }}">
            {{-- Smaller on phones (not in the design): at 230px the menu button didn't fit on screens under ~490px. --}}
            <x-website.logo width="230" class="max-w-full max-sm:w-[110px] max-xs:w-[88px]" />
        </a>

        <div class="flex items-center gap-[15px] max-sm:gap-[10px] max-xs:gap-1.5">
            <ul class="flex list-none items-center gap-[15px] max-md:hidden" role="list">
                @foreach ($links as $link)
                    <li>
                        <a
                            href="{{ route($link['route']) }}"
                            @class([
                                'relative font-sans text-[12px] font-semibold tracking-[0.18em] uppercase transition-colors duration-250 after:absolute after:-bottom-[5px] after:left-0 after:h-px after:bg-brand after:transition-[width] after:duration-380 after:ease-smooth hover:text-white/95 hover:after:w-full rtl:font-arabic rtl:tracking-[0.02em] rtl:normal-case',
                                'text-white after:w-full' => ! $transparent && request()->routeIs($link['active'] ?? $link['route']),
                                'text-white/90 after:w-0' => $transparent || ! request()->routeIs($link['active'] ?? $link['route']),
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
                class="relative flex h-[31px] w-20 shrink-0 cursor-pointer items-center rounded-full border border-[rgba(160,50,72,0.45)] bg-[rgba(160,50,72,0.18)] p-[3px] outline-none backdrop-blur-[10px] transition-[box-shadow,border-color] duration-300 hover:border-[rgba(160,50,72,0.7)] rtl:mr-3 rtl:max-xs:mr-0"
                aria-label="{{ __('Switch language') }}"
            >
                <span class="relative z-1 flex-1 text-center font-sans text-[11px] leading-none font-bold tracking-[0.05em] transition-colors duration-300 select-none" :class="arabic ? 'text-white/50' : 'text-white'">EN</span>
                <span class="absolute top-[3px] left-[3px] z-0 h-[calc(100%-6px)] w-[calc(50%-3px)] rounded-full bg-brand transition-transform duration-350 ease-spring" :class="arabic && 'translate-x-full'"></span>
                <span class="relative z-1 flex-1 text-center font-arabic text-[11px] leading-none font-bold tracking-[0.05em] transition-colors duration-300 select-none" :class="arabic ? 'text-white' : 'text-white/50'">عر</span>
            </a>

            {{-- Wishlist: the saved units and projects (browser only), with their number --}}
            <a
                href="{{ route('wishlist') }}"
                @class([
                    $iconButton.' '.$tooltip.' transition-[background,border-color,color] duration-250 hover:border-[#c8102e] hover:bg-[rgba(200,16,46,0.85)] hover:text-white',
                    'border-[#c8102e]! bg-[rgba(200,16,46,0.85)] text-white! [&_path]:fill-current' => request()->routeIs('wishlist'),
                ])
                aria-label="{{ __('Wishlist') }}"
                data-tooltip="{{ __('Wishlist') }}"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-[14px] flex-none" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" /></svg>
                <span
                    x-show="$store.favourites.ids.length"
                    x-text="$store.favourites.ids.length > 99 ? '99+' : $store.favourites.ids.length"
                    style="display: none"
                    class="pointer-events-none absolute -top-1.5 -right-1.5 flex h-4 min-w-4 items-center justify-center rounded-full border-[1.5px] border-black/15 bg-[#c8102e] px-[3px] font-sans text-[9px] leading-none font-bold text-white"
                ></span>
            </a>

            @if ($account)
                {{-- Signed in: the design's name pill, with a small menu to sign out. --}}
                <div x-data="{ menu: false }" @click.outside="menu = false" @keydown.escape.window="menu = false" class="relative">
                    <button
                        type="button"
                        @click="menu = ! menu"
                        :aria-expanded="menu"
                        class="inline-flex h-[31px] max-w-[160px] cursor-pointer items-center gap-[7px] rounded-full border border-white/20 bg-white/8 ps-1 pe-[10px] text-[11.5px] font-semibold whitespace-nowrap text-white transition-[background,border-color] duration-250 hover:border-[#c8102e] max-sm:pe-1 rtl:font-arabic"
                        aria-label="{{ __('My account') }}"
                    >
                        <span class="flex size-[22px] flex-none items-center justify-center rounded-full bg-[#c8102e] text-[10px] font-bold text-white">{{ mb_strtoupper(mb_substr($account->name, 0, 1)) }}</span>
                        <span class="truncate max-sm:hidden">{{ \Illuminate\Support\Str::of($account->name)->before(' ') }}</span>
                    </button>
                    <div
                        x-show="menu"
                        x-transition.opacity.duration.150ms
                        x-cloak
                        class="absolute top-[calc(100%+10px)] right-0 z-10 w-60 rounded-xl border border-white/10 bg-[rgba(16,16,16,0.97)] p-2 shadow-[0_12px_32px_rgba(0,0,0,0.45)] backdrop-blur-[20px] rtl:right-auto rtl:left-0"
                    >
                        <div class="border-b border-white/8 px-3 pt-2 pb-3">
                            <p class="truncate text-[13px] font-semibold text-white rtl:font-arabic">{{ $account->name }}</p>
                            <p class="truncate text-[11.5px] text-white/50" dir="ltr">{{ $account->email }}</p>
                        </div>
                        <form method="POST" action="{{ route('account.logout') }}" class="pt-1">
                            @csrf
                            <button type="submit" class="flex w-full cursor-pointer items-center gap-2 rounded-lg px-3 py-2.5 text-start text-[13px] text-white/80 transition-[background,color] duration-200 hover:bg-[rgba(200,16,46,0.15)] hover:text-white rtl:font-arabic">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="rtl:-scale-x-100" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" /><polyline points="16 17 21 12 16 7" /><line x1="21" y1="12" x2="9" y2="12" /></svg>
                                {{ __('Sign out') }}
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <button
                    type="button"
                    @click="$dispatch('open-auth')"
                    class="{{ $iconButton }} {{ $tooltip }} font-[inherit] transition-[background,border-color,color] duration-250 hover:border-[#c8102e] hover:bg-[rgba(200,16,46,0.85)] hover:text-white"
                    aria-label="{{ __('Sign in or register') }}"
                    data-tooltip="{{ __('Sign in') }}"
                >
                    <x-website.icons.user class="size-[14px] flex-none" />
                </button>
            @endif

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
        class="pointer-events-none fixed inset-0 z-199 flex flex-col overflow-y-auto bg-[rgba(8,8,8,0.97)] px-8 pt-[calc(var(--spacing-nav)+24px)] pb-12 backdrop-blur-[20px] transition-[translate,visibility] duration-380 ease-drawer invisible ltr:translate-x-full rtl:-translate-x-full"
        {{-- Closed: also invisible (it stays visible while sliding out). Off-screen alone isn't enough: when the home
             page shrinks over the footer, the closed drawer showed through the gap as a black line on the side. --}}
        :class="{ 'pointer-events-auto visible translate-x-0': open, 'pointer-events-none invisible ltr:translate-x-full rtl:-translate-x-full': ! open }"
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
                            'text-brand dark:text-ink' => request()->routeIs($link['active'] ?? $link['route']),
                            'text-white/85 dark:text-ink-60' => ! request()->routeIs($link['active'] ?? $link['route']),
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
