{{--
    Public website layout. Language and direction come from the app locale; the theme (light / dark) from the
    "theme" cookie, so the page is rendered in the right theme from the start.
    revealFooter (home): the page content and its footer spacer scroll over the fixed footer, which they reveal at
    the end; other pages end with a normal footer, as in the design.
--}}
@props(['title' => null, 'description' => null, 'transparentNav' => false, 'revealFooter' => false])

@php
    $dark = request()->cookie('theme') === 'dark';
@endphp

<!DOCTYPE html>
<html
    lang="{{ app()->getLocale() }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    data-theme="{{ $dark ? 'dark' : 'light' }}"
    class="overflow-x-clip antialiased [text-rendering:optimizeLegibility] transition-colors duration-450 rtl:tracking-normal rtl:normal-case"
>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        {{--  The track URL is used by the possible leads tracking script to send the lead data to the server.--}}
        <meta name="track-url" content="{{ route('api.possible-leads.track', absolute: false) }}" />
        <meta name="compare-max" content="{{ \App\Support\Website\CompareUnits::MAX }}" />
        {{-- Signed in: the wishlist saved to the account, and where heart clicks are saved ($store.favourites). --}}
        @if ($account = auth('website')->user())
            <meta name="account-favourites" content="{{ json_encode(\App\Support\Website\Favourites::of($account)) }}" data-url="{{ route('account.favourites', absolute: false) }}" />
        @endif
        <title>{{ $title ? $title.' — '.__('Akar Town') : __('Akar Town — Exclusive Real Estate') }}</title>
        <meta name="description" content="{{ $description ?? __("Akar Town — Discover exceptional luxury properties across Egypt's finest locations.") }}" />
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" />

        {{-- Both fonts are declared on every page (English pages show "عر", Arabic ones English); a font file is only
             downloaded when a page uses it, and only Archivo is preloaded. --}}
        @fonts(['archivo', 'noto-kufi-arabic'])
        @vite(['resources/css/website.css', 'resources/js/website.js'])
        @livewireStyles
        {{-- A page's first big photo, asked for early (e.g. the home hero). --}}
        @stack('head')
    </head>

    <body class="min-h-screen overflow-x-clip bg-page font-sans leading-[1.6] text-ink selection:bg-brand selection:text-white rtl:text-right rtl:font-arabic">
        {{ $before ?? '' }}

        <div id="site-wrap" class="relative z-2">
            <x-website.navbar :transparent="$transparentNav" />

            {{ $slot }}

            @if ($revealFooter)
                {{-- Drives the footer reveal scroll range. --}}
                <div id="footer-spacer" x-data class="h-dvh" :class="{ 'h-dvh': ! $store.footer.static, 'h-0': $store.footer.static }" aria-hidden="true"></div>
            @endif
        </div>

        {{-- Outside the page wrap, so its scroll transforms don't move these fixed layers. --}}
        <x-website.gallery-lightbox />
        <x-website.project-lightbox />
        <x-website.compare-bar />
        {{-- "Sign in or Register", opened by the navbar's account button (event open-auth). --}}
        <livewire:website.auth-modal />
        <livewire:website.submit-property />
        {{-- A one-time message from the server (e.g. a blocked account signed out), shown for a few seconds. --}}
        @if ($notice = session(\Modules\WebsiteUsers\Http\Middleware\EnsureWebsiteUserIsActive::NOTICE))
            <div
                x-data="{ shown: true }"
                x-init="setTimeout(() => (shown = false), 5000)"
                x-show="shown"
                x-transition.opacity.duration.300ms
                class="fixed bottom-8 left-1/2 z-9999 max-w-[calc(100vw-32px)] -translate-x-1/2 rounded-full bg-ink px-[22px] py-3 text-center text-[13px] font-medium text-paper rtl:font-arabic"
                role="status"
            >{{ $notice }}</div>
        @endif

        <x-website.footer :reveal="$revealFooter" />

        @livewireScripts
    </body>
</html>
