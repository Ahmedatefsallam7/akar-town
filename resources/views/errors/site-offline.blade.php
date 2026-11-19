{{--
    The page visitors see while the site is Offline (dashboard switch, EnsureSiteIsLive): the logo, a short note, how to
    reach the office meanwhile, and the language switch (the locale route stays open while offline).
    It must still read well when the built assets are missing (the logo has a fixed width; plain text otherwise).
--}}
@php
    $business = app(\Modules\GeneralSettings\Settings\BusinessInfoSettings::class);
    $phone = $business->hotline ?: null;
    $email = $business->email ?: null;
    $other = app()->getLocale() === 'ar' ? 'en' : 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __("We'll be back soon") }} · {{ __('Akar Town') }}</title>
    @if (is_file(public_path('build/manifest.json')))
        @fonts(['archivo', 'noto-kufi-arabic'])
        @vite('resources/css/website.css')
    @endif
</head>
<body class="relative flex min-h-dvh flex-col overflow-hidden bg-night font-sans text-cream rtl:font-arabic">
    {{-- A soft brand-red glow behind the logo, and a thin red line on top. --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 bg-[radial-gradient(60%_45%_at_50%_38%,rgb(200_16_46/0.22),transparent_70%)]"></div>
    <div aria-hidden="true" class="absolute inset-x-0 top-0 h-1 bg-brand"></div>

    <header class="relative flex justify-end px-6 pt-6 max-sm:px-4">
        <a href="{{ route('locale.switch', $other) }}" hreflang="{{ $other }}"
           class="rounded-full border border-line-md px-4 py-1.5 text-sm font-medium text-cream/80 transition-colors hover:border-cream/40 hover:text-cream focus-visible:outline-2 focus-visible:outline-brand {{ $other === 'ar' ? 'font-arabic' : 'font-sans' }}">
            {{ config('app.available_locales')[$other] }}
        </a>
    </header>

    <main class="relative mx-auto flex w-full max-w-xl flex-1 flex-col items-center justify-center px-6 py-12 text-center max-sm:px-4">
        <x-website.logo width="260" class="h-auto w-[260px] max-w-full max-sm:w-[200px]" />

        <p class="mt-12 inline-flex items-center gap-2 rounded-full border border-brand/40 bg-brand/10 px-4 py-1.5 text-xs font-semibold tracking-[0.14em] text-cream uppercase rtl:tracking-normal">
            <span class="relative flex size-2">
                <span class="absolute inline-flex size-full animate-ping rounded-full bg-brand opacity-75"></span>
                <span class="relative inline-flex size-2 rounded-full bg-brand"></span>
            </span>
            {{ __('Under maintenance') }}
        </p>

        <h1 class="mt-6 text-4xl leading-tight font-bold text-white max-sm:text-3xl">{{ __("We'll be back soon") }}</h1>
        <p class="mt-4 text-base leading-relaxed text-cream/70">{{ __('admin.site_status.offline_message') }}</p>

        @if ($phone || $email)
            <div class="mt-10 w-full border-t border-line-md pt-8">
                <p class="text-sm text-cream/55">{{ __('Need help in the meantime?') }}</p>
                <div class="mt-4 flex flex-wrap justify-center gap-3">
                    @if ($phone)
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}"
                           class="rounded-full bg-brand px-6 py-2.5 text-sm font-semibold text-white transition-opacity hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                            {{ __('Call us') }} · <span dir="ltr">{{ $phone }}</span>
                        </a>
                    @endif
                    @if ($email)
                        <a href="mailto:{{ $email }}"
                           class="rounded-full border border-line-md px-6 py-2.5 text-sm font-semibold text-cream transition-colors hover:border-cream/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                            {{ __('Email us') }}
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </main>

    <footer class="relative px-6 pb-6 text-center text-xs text-cream/40">
        © {{ now()->year }} {{ __('Akar Town') }}. {{ __('All rights reserved.') }}
    </footer>
</body>
</html>
