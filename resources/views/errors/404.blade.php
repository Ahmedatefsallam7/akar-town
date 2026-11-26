{{--
    "Page not found" in the website's look (navbar and footer), so a visitor with an old or wrong link can carry on:
    home, properties or projects. Unknown addresses reach it through the web routes' fallback, so the language and the
    session are set as on any page.
--}}
<x-website.layout :title="__('Page not found')">
    @push('head')
        <meta name="robots" content="noindex" />
    @endpush

    <section class="relative flex min-h-[max(80vh,560px)] flex-col items-center justify-center overflow-hidden bg-night px-6 pt-nav pb-20 text-center text-cream max-sm:px-4">
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 bg-[radial-gradient(60%_45%_at_50%_45%,rgb(200_16_46/0.22),transparent_70%)]"></div>

        <p class="relative mt-16 font-sans text-[clamp(96px,16vw,180px)] leading-none font-light tracking-[-0.04em] text-white/90">4<span class="text-brand">0</span>4</p>
        <h1 class="relative mt-6 text-[clamp(26px,3.4vw,40px)] leading-tight font-light text-white rtl:font-arabic">{{ __('Page not found') }}</h1>
        <p class="relative mt-4 max-w-md text-base leading-relaxed text-cream/70 rtl:font-arabic">{{ __("The page you're looking for was moved, removed or never existed.") }}</p>

        <div class="relative mt-10 flex flex-wrap justify-center gap-3">
            <a href="{{ route('home') }}" class="rounded-full bg-brand px-6 py-2.5 text-sm font-semibold text-white transition-opacity hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white rtl:font-arabic">{{ __('Back to Home') }}</a>
            <a href="{{ route('properties') }}" class="rounded-full border border-line-md px-6 py-2.5 text-sm font-semibold text-cream transition-colors hover:border-cream/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white rtl:font-arabic">{{ __('Browse Properties') }}</a>
            <a href="{{ route('projects') }}" class="rounded-full border border-line-md px-6 py-2.5 text-sm font-semibold text-cream transition-colors hover:border-cream/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white rtl:font-arabic">{{ __('Our Projects') }}</a>
        </div>
    </section>
</x-website.layout>
