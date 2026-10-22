{{--
    Site footer (the design's .site-footer). reveal (home page): fixed behind the page and revealed by the footer
    spacer's scroll range (footerReveal in resources/js/website.js), a normal footer when its content is taller than
    the screen. Otherwise (other pages, as in the design): a normal full-screen-high footer.
--}}
@props(['reveal' => false])

@php
    $business = app(\Modules\GeneralSettings\Settings\BusinessInfoSettings::class);
    $stats = \App\Support\Website\FooterStats::get();
    $socials = array_filter([
        'instagram' => $business->social_instagram,
        'facebook' => $business->social_facebook,
        'linkedin' => $business->social_linkedin,
        'youtube' => $business->social_youtube,
    ]);
    $label = 'mb-[0.9rem] block text-[0.68rem] font-semibold tracking-[0.16em] text-cream/55 uppercase rtl:tracking-[0.04em]';
    $link = 'text-[0.92rem] font-medium text-cream/85 transition-colors duration-300 hover:text-cream focus-visible:text-cream';
@endphp

<footer
    x-data="footerReveal(@js($reveal))"
    @if ($reveal)
        class="fixed inset-x-0 bottom-0 z-1 h-dvh overflow-hidden bg-night text-white"
        :class="{ 'fixed inset-x-0 bottom-0 h-dvh': ! static, 'relative h-auto': static }"
    @else
        class="relative min-h-dvh w-full overflow-hidden bg-night text-white"
    @endif
>
    <div
        x-ref="bg"
        @if ($reveal)
            class="absolute inset-0 bg-cover bg-center will-change-transform [filter:grayscale(0.35)_brightness(0.42)_contrast(1.1)] [transform:scale(1.18)]"
            :class="{ '[transform:scale(1.18)]': ! static, '[transform:scale(1)]': static }"
        @else
            class="absolute inset-0 bg-cover bg-center [filter:grayscale(0.35)_brightness(0.42)_contrast(1.1)]"
        @endif
        {{-- Set when the visitor nears the footer (footerReveal.lazyBackground): it isn't fetched with the page. Phones
             take the 900 px copy. --}}
        data-bg="{{ asset('images/website/footer-bg.webp') }}"
        data-bg-small="{{ asset('images/website/footer-bg-small.webp') }}"
        aria-hidden="true"
    ></div>
    <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(0deg,#080808_12%,rgba(8,8,8,0.05)_72%)]" aria-hidden="true"></div>

    <div
        x-ref="inner"
        @if ($reveal)
            class="relative z-1 flex h-full flex-col justify-between pt-[clamp(1.5rem,3.5vh,2.5rem)] pb-[clamp(1rem,2vh,1.5rem)] *:shrink-0"
            :class="{ 'h-full pb-[clamp(1rem,2vh,1.5rem)]': ! static, 'h-auto gap-[clamp(1.5rem,4vh,2.5rem)] pb-[clamp(1.25rem,3vh,1.75rem)]': static }"
        @else
            class="relative z-1 flex min-h-dvh flex-col justify-between pt-[clamp(1.5rem,3.5vh,2.5rem)] pb-[clamp(1rem,2vh,1.5rem)] *:shrink-0"
        @endif
    >
        <div class="mx-auto grid w-full max-w-[1320px] grid-cols-[1.4fr_1fr_1fr_1.6fr] gap-[clamp(1.5rem,3vw,2.5rem)] px-12 max-lg:grid-cols-2 max-lg:gap-y-[clamp(1.75rem,5vh,2.5rem)] max-sm:gap-x-5 max-sm:gap-y-7">
            {{-- Brand --}}
            <div class="max-sm:order-1 max-sm:col-span-full" data-reveal>
                <a href="{{ route('home') }}" class="mb-4 block" aria-label="{{ __('Akar Town — Home') }}">
                    <x-website.logo class="block h-[26px] w-auto" />
                </a>
                <p class="mb-[1.4rem] max-w-[30ch] text-[0.85rem] leading-[1.65] text-cream/78">{{ $business->{'mini_description_'.app()->getLocale()} }}</p>
                <div class="mb-[1.4rem] flex gap-[clamp(1rem,2.5vw,1.75rem)]">
                    @foreach ([
                        [$stats['projects'], __('Projects')],
                        [$stats['developers'], __('Developers')],
                        [$stats['years'].'+', __('Years')],
                    ] as [$value, $statLabel])
                        <div>
                            <span class="mb-[0.28rem] block text-[clamp(1.4rem,2.5vw,1.9rem)] leading-none font-bold text-[#c8102e]">{{ $value }}</span>
                            <span class="block text-[0.6rem] font-bold tracking-[0.1em] text-cream/60 uppercase">{{ $statLabel }}</span>
                        </div>
                    @endforeach
                </div>
                @if ($socials)
                    <div class="flex gap-2">
                        @foreach ($socials as $network => $url)
                            <a
                                href="{{ $url }}"
                                class="flex size-9 items-center justify-center rounded-full border border-cream/35 text-cream/80 transition-[border-color,color] duration-300 hover:border-cream hover:text-cream focus-visible:border-cream focus-visible:text-cream"
                                aria-label="{{ ucfirst($network) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <x-dynamic-component :component="'website.icons.'.$network" />
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Explore --}}
            <div data-reveal>
                <span class="{{ $label }}">{{ __('Explore') }}</span>
                <ul class="m-0 flex list-none flex-col gap-[0.55rem] p-0 max-sm:gap-[0.45rem]">
                    <li><a class="{{ $link }}" href="{{ route('properties') }}">{{ __('Properties') }}</a></li>
                    <li><a class="{{ $link }}" href="{{ route('projects') }}">{{ __('Projects') }}</a></li>
                    <li><a class="{{ $link }}" href="{{ route('developers') }}">{{ __('Developers') }}</a></li>
                    <li><a class="{{ $link }}" href="{{ route('locations') }}">{{ __('Locations') }}</a></li>
                </ul>
            </div>

            {{-- Company --}}
            <div data-reveal>
                <span class="{{ $label }}">{{ __('Company') }}</span>
                <ul class="m-0 flex list-none flex-col gap-[0.55rem] p-0 max-sm:gap-[0.45rem]">
                    <li><a class="{{ $link }}" href="{{ route('about') }}">{{ __('About Us') }}</a></li>
                    <li><a class="{{ $link }}" href="{{ route('careers') }}">{{ __('Careers') }}</a></li>
                    <li><a class="{{ $link }}" href="{{ route('contact') }}">{{ __('Contact Us') }}</a></li>
                </ul>
            </div>

            {{-- Property matching (Livewire: saved as a Contact Us message and sent to the CRM) --}}
            <div id="property-matching" class="max-lg:col-span-full max-sm:order-4" data-reveal>
                <livewire:website.property-matching />
            </div>
        </div>

        {{-- Full-width wordmark, sized to fit by footerReveal --}}
        <div x-ref="mark" @class(['pointer-events-none relative my-[clamp(1.5rem,4vh,3rem)] flex w-full items-center justify-center overflow-hidden [@media(max-height:720px)]:my-2', 'min-h-0 flex-1' => $reveal]) data-reveal aria-hidden="true">
            <span x-ref="markText" class="inline-block bg-[linear-gradient(rgba(246,245,242,0.5)_0%,rgba(246,245,242,0.4)_45%,rgba(246,245,242,0.1)_72%,rgba(246,245,242,0)_88%)] bg-clip-text text-[clamp(4.5rem,16vw,13rem)] leading-[0.86] font-extrabold tracking-[-0.03em] whitespace-nowrap text-transparent select-none [filter:grayscale(0.4)_brightness(0.6)_contrast(1.05)]">AKAR TOWN</span>
        </div>

        {{-- Bottom bar --}}
        <div class="mx-auto flex w-full max-w-[1320px] flex-wrap items-center justify-between gap-3 border-t border-cream/12 px-12 pt-[clamp(1rem,2.5vh,1.5rem)] text-[0.75rem] text-cream/60 max-sm:justify-start" data-reveal>
            <span>
                &copy; {{ __('Copyright :year. All Rights Reserved To', ['year' => now()->year]) }}
                <a
                    href="https://www.8worx.com/"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="bg-[linear-gradient(currentColor,currentColor)] bg-[size:100%_1px] bg-[position:0_100%] bg-no-repeat font-semibold text-cream transition-[background-size] duration-350 hover:bg-[size:0_1px] hover:bg-[position:100%_100%] focus-visible:bg-[size:0_1px] focus-visible:bg-[position:100%_100%]"
                >8WORX</a>
            </span>
            <div class="flex gap-5">
                <a href="{{ route('privacy') }}" class="text-cream/60 transition-colors duration-300 hover:text-cream focus-visible:text-cream">{{ __('Privacy Policy') }}</a>
                <a href="{{ route('terms') }}" class="text-cream/60 transition-colors duration-300 hover:text-cream focus-visible:text-cream">{{ __('Terms') }}</a>
            </div>
        </div>
    </div>
</footer>
