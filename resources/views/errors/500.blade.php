{{--
    "Something went wrong" (a server error). On its own, with no database or settings: what broke may be exactly
    that. It must still read well when the built assets are missing (plain text otherwise).
--}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('Something went wrong') }} · {{ __('Akar Town') }}</title>
    @if (is_file(public_path('build/manifest.json')))
        @vite('resources/css/website.css')
    @endif
</head>
<body class="relative flex min-h-dvh flex-col items-center justify-center overflow-hidden bg-night px-6 text-center font-sans text-cream rtl:font-arabic">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 bg-[radial-gradient(60%_45%_at_50%_45%,rgb(200_16_46/0.22),transparent_70%)]"></div>
    <div aria-hidden="true" class="absolute inset-x-0 top-0 h-1 bg-brand"></div>

    <p class="relative font-sans text-[clamp(96px,16vw,180px)] leading-none font-light tracking-[-0.04em] text-white/90">5<span class="text-brand">0</span>0</p>
    <h1 class="relative mt-6 text-[clamp(26px,3.4vw,40px)] leading-tight font-light text-white">{{ __('Something went wrong') }}</h1>
    <p class="relative mt-4 max-w-md text-base leading-relaxed text-cream/70">{{ __("We're on it. Please try again in a few minutes.") }}</p>
    <a href="{{ url('/') }}" class="relative mt-10 rounded-full bg-brand px-6 py-2.5 text-sm font-semibold text-white transition-opacity hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">{{ __('Back to Home') }}</a>
</body>
</html>
