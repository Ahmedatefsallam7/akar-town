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
    $fieldLabel = 'mb-[0.45rem] block text-[0.6rem] font-bold tracking-[0.1em] text-cream/60 uppercase';
    $input = 'min-w-0 flex-1 rounded-[10px] border-[1.5px] border-cream/14 bg-cream/5 px-3 py-[10px] text-[0.8rem] text-cream placeholder:text-cream/42 dark:border-line-md dark:bg-surface-2 dark:text-ink dark:placeholder:text-ink-30';
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
        style="background-image: url('{{ asset('images/website/footer-bg.webp') }}')"
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
                        [$stats['listings'], __('Active Listings')],
                        [$stats['locations'], __('Locations')],
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

            {{-- Properties --}}
            <div data-reveal>
                <span class="{{ $label }}">{{ __('Properties') }}</span>
                <ul class="m-0 flex list-none flex-col gap-[0.55rem] p-0 max-sm:gap-[0.45rem]">
                    <li><a class="{{ $link }}" href="{{ route('properties') }}">{{ __('All Properties') }}</a></li>
                    <li><a class="{{ $link }}" href="#">{{ __('Villas & Compounds') }}</a></li>
                    <li><a class="{{ $link }}" href="#">{{ __('Penthouse Collection') }}</a></li>
                    <li><a class="{{ $link }}" href="#">{{ __('Off-Plan Projects') }}</a></li>
                    <li><a class="{{ $link }}" href="#">{{ __('North Coast') }}</a></li>
                    <li><a class="{{ $link }}" href="{{ route('developers') }}">{{ __('Developers') }}</a></li>
                    <li><a class="{{ $link }}" href="{{ route('properties') }}">{{ __('Compare Units') }}</a></li>
                </ul>
            </div>

            {{-- Company --}}
            <div data-reveal>
                <span class="{{ $label }}">{{ __('Company') }}</span>
                <ul class="m-0 flex list-none flex-col gap-[0.55rem] p-0 max-sm:gap-[0.45rem]">
                    <li><a class="{{ $link }}" href="{{ route('about') }}">{{ __('About Us') }}</a></li>
                    <li><a class="{{ $link }}" href="#">{{ __('Our Team') }}</a></li>
                    <li><a class="{{ $link }}" href="#">{{ __('Success Stories') }}</a></li>
                    <li><a class="{{ $link }}" href="{{ route('careers') }}">{{ __('Careers') }}</a></li>
                    <li><a class="{{ $link }}" href="#">{{ __('Press & Media') }}</a></li>
                    <li><a class="{{ $link }}" href="{{ route('contact') }}">{{ __('Contact Us') }}</a></li>
                </ul>
            </div>

            {{-- Property matching --}}
            <div class="max-lg:col-span-full max-sm:order-4" data-reveal x-data="{ type: 'apartment' }">
                <span class="{{ $label }}">{{ __('Property Matching') }}</span>
                <h3 class="mb-2 text-[clamp(1.1rem,1.8vw,1.5rem)] leading-[1.2] font-bold text-cream [&_em]:text-[#c8102e] [&_em]:not-italic">{!! __('Find Your <em>Perfect</em> Unit') !!}</h3>
                <p class="mb-4 text-[0.82rem] leading-[1.55] text-cream/75">{{ __("Tell us what you need — we'll alert you the moment it's listed.") }}</p>
                <div class="mb-4 flex flex-wrap gap-[0.4rem]" role="group" aria-label="{{ __('Property type') }}">
                    @foreach (['apartment' => __('Apartment'), 'villa' => __('Villa'), 'penthouse' => __('Penthouse'), 'chalet' => __('Chalet')] as $value => $typeLabel)
                        <button
                            type="button"
                            @click="type = @js($value)"
                            class="cursor-pointer rounded-[99px] border px-[0.9rem] py-[0.38rem] text-[0.78rem] leading-none font-medium transition-[background,border-color,color] duration-200 hover:border-[#c8102e] hover:bg-[#c8102e] hover:text-white"
                            :class="type === @js($value) ? 'border-[#c8102e] bg-[#c8102e] text-white' : 'border-cream/38 bg-transparent text-cream/88'"
                        >{{ $typeLabel }}</button>
                    @endforeach
                </div>
                <form novalidate @submit.prevent>
                    <span class="{{ $fieldLabel }}">{{ __('Budget Range (EGP)') }}</span>
                    <div class="mb-[0.85rem] flex items-center gap-[0.4rem] max-xs:flex-wrap">
                        <input type="number" min="0" class="{{ $input }}" placeholder="{{ __('Minimum') }}" aria-label="{{ __('Minimum budget') }}" />
                        <span class="shrink-0 text-[1rem] text-cream/55" aria-hidden="true">—</span>
                        <input type="number" min="0" class="{{ $input }}" placeholder="{{ __('Maximum') }}" aria-label="{{ __('Maximum budget') }}" />
                    </div>
                    <span class="{{ $fieldLabel }}">{{ __('Your Email') }}</span>
                    <div class="flex items-stretch gap-[0.45rem]">
                        <input type="email" required class="{{ $input }}" placeholder="you@example.com" aria-label="{{ __('Your email') }}" />
                        <button
                            type="submit"
                            class="flex size-[38px] shrink-0 cursor-pointer items-center justify-center rounded-full bg-[#c8102e] text-white transition-[background] duration-250 hover:bg-[#a60d24]"
                            aria-label="{{ __('Submit') }}"
                        >
                            <svg width="16" height="14" viewBox="0 0 16 14" fill="none" aria-hidden="true">
                                <path d="M7.99997 0.414307C7.99997 1.11051 8.68991 2.15231 9.38737 3.02724C10.2855 4.15371 11.3574 5.13777 12.5873 5.88911C13.5087 6.45171 14.6276 6.99177 15.5264 6.99177M7.99997 13.5855C7.99997 12.8893 8.68991 11.8475 9.38737 10.9726C10.2855 9.84617 11.3574 8.86204 12.5873 8.11071C13.5087 7.54811 14.6276 7.00804 15.5264 7.00804M15.5264 6.99991H0.473572" stroke="currentColor" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Full-width wordmark, sized to fit by footerReveal --}}
        <div x-ref="mark" @class(['pointer-events-none relative my-[clamp(1.5rem,4vh,3rem)] flex w-full items-center justify-center overflow-hidden', 'min-h-0 flex-1' => $reveal]) data-reveal aria-hidden="true">
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
                <a href="#" class="text-cream/60 transition-colors duration-300 hover:text-cream focus-visible:text-cream">{{ __('Privacy Policy') }}</a>
                <a href="#" class="text-cream/60 transition-colors duration-300 hover:text-cream focus-visible:text-cream">{{ __('Terms') }}</a>
            </div>
        </div>
    </div>
</footer>
