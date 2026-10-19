{{--
    Home hero (the design's .hero): project slides with the liquid wipe, thumbnails rail, progress and prev / next,
    and the preview cards that follow the pointer. Behaviour: resources/js/website/hero.js.
    @var list<array> $slides  App\Support\Website\HomeHero::slides()
--}}
@php
    $thumbs = array_slice($slides, 0, 6);
    $more = count($slides) - count($thumbs);
    $navButton = 'group relative flex size-[54px] cursor-pointer items-center justify-center rounded-full border border-glass-border bg-glass text-ink outline-none backdrop-blur-[20px] backdrop-saturate-180 transition-[background,border-color] duration-350 hover:border-brand hover:bg-brand';
@endphp

<section
    x-data="heroSlider(@js($slides))"
    class="relative h-[88vh] min-h-[580px] w-full overflow-hidden"
    aria-label="{{ __('Featured luxury properties') }}"
    aria-roledescription="carousel"
>
    {{-- Preview cards that trail the pointer --}}
    <div data-previews class="pointer-events-none absolute inset-0 z-6 motion-reduce:hidden" aria-hidden="true">
        @foreach (array_slice($slides, 0, 5) as $i => $slide)
            <div data-preview class="absolute top-0 left-0 -mt-[37px] -ml-[55px] h-[74px] w-[110px] overflow-hidden rounded-md opacity-0 shadow-[0_18px_38px_-18px_rgba(0,0,0,0.7)] outline-[1.5px] -outline-offset-[1.5px] outline-white/14 transition-[outline-color] duration-350 outline-solid will-change-[transform,opacity]" style="z-index: {{ 5 - $i }}">
                <img alt="" loading="lazy" class="block size-full object-cover" />
            </div>
        @endforeach
    </div>

    {{-- Thumbnails rail (a bottom strip on small screens) --}}
    <aside
        data-thumbs
        class="absolute inset-y-0 start-0 z-30 flex w-[124px] flex-col items-start justify-center gap-2 bg-[linear-gradient(170deg,rgb(0_0_0/0.18)_0%,rgb(0_0_0/0.1)_55%,rgb(0_0_0/0.15)_100%)] ps-12 pe-3 pt-[calc(var(--spacing-nav)+5px)] pb-5 shadow-[inset_-1px_0_0_rgb(255_255_255/0.1),4px_0_24px_rgba(0,0,0,0.1)] backdrop-blur-[24px] backdrop-brightness-82 backdrop-saturate-120 before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:z-1 before:h-[72px] before:bg-[linear-gradient(rgb(0_0_0/0.38)_0%,transparent_100%)] after:pointer-events-none after:absolute after:inset-x-0 after:bottom-0 after:z-1 after:h-[72px] after:bg-[linear-gradient(to_top,rgb(0_0_0/0.38)_0%,transparent_100%)] rtl:shadow-[inset_1px_0_0_rgb(255_255_255/0.1),-4px_0_24px_rgba(0,0,0,0.1)] max-md:inset-x-0 max-md:top-auto max-md:bottom-0 max-md:h-[90px] max-md:w-full max-md:flex-row max-md:justify-start max-md:gap-[10px] max-md:overflow-x-auto max-md:overflow-y-hidden max-md:px-4 max-md:py-[10px] max-md:[scrollbar-width:none] max-md:before:inset-y-0 max-md:before:right-auto max-md:before:left-0 max-md:before:h-full max-md:before:w-12 max-md:before:bg-[linear-gradient(to_right,rgb(0_0_0/0.38)_0%,transparent_100%)] max-md:after:inset-y-0 max-md:after:right-0 max-md:after:left-auto max-md:after:h-full max-md:after:w-12 max-md:after:bg-[linear-gradient(to_left,rgb(0_0_0/0.38)_0%,transparent_100%)]"
        role="tablist"
        aria-label="{{ __('Project thumbnails') }}"
    >
        @foreach ($thumbs as $i => $slide)
            <button
                type="button"
                data-thumb="{{ $i }}"
                @click="navigate({{ $i }})"
                role="tab"
                :aria-selected="active === {{ $i }}"
                :tabindex="active === {{ $i }} ? 0 : -1"
                aria-label="{{ __('View :title', ['title' => $slide['title']]) }}"
                class="group relative z-2 flex w-16 cursor-pointer items-center justify-center gap-0 rounded-xl border p-[5px] outline-none transition-all duration-400 ease-out-ex before:absolute before:inset-0 before:rounded-[11px] before:bg-transparent before:transition-[background] before:duration-400 hover:before:bg-white/8 max-md:w-auto max-md:min-w-[130px] max-md:shrink-0 max-md:flex-row max-md:gap-[10px] max-md:rounded-[10px] max-md:px-[10px] max-md:py-[7px]"
                :class="active === {{ $i }} ? 'border-brand/55 bg-brand/14' : 'border-transparent'"
            >
                <span
                    class="size-[54px] shrink-0 overflow-hidden rounded-[10px] transition-all duration-400 ease-out-ex max-md:size-10"
                    :class="active === {{ $i }} && 'shadow-[0_4px_16px_rgba(0,0,0,0.55),0_0_0_2px_var(--color-brand)]'"
                >
                    <img src="{{ $slide['thumb'] }}" alt="{{ $slide['title'] }}" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" class="block size-full object-cover transition-transform duration-600 will-change-transform group-hover:scale-108" />
                </span>
                <span
                    class="absolute top-[10px] right-0 w-[2.5px] rounded-l-[2px] bg-[linear-gradient(to_bottom,var(--color-brand-lt),var(--color-brand))] transition-[height] duration-400 ease-out-ex max-md:hidden"
                    :class="active === {{ $i }} ? 'h-[calc(100%-20px)]' : 'h-0'"
                ></span>
            </button>
        @endforeach

        @if ($more > 0)
            <a
                href="#projects"
                class="group relative flex w-16 cursor-pointer flex-col items-center justify-center gap-0 rounded-xl border border-white/10 bg-transparent p-[5px] outline-none transition-all duration-400 ease-out-ex before:absolute before:inset-0 before:rounded-[11px] before:bg-transparent before:transition-[background] before:duration-400 hover:border-brand/40 hover:before:bg-white/8 max-md:w-auto max-md:shrink-0 max-md:flex-row max-md:rounded-[10px] max-md:px-[10px] max-md:py-[7px]"
                aria-label="{{ __('Explore :count more projects', ['count' => $more]) }}"
            >
                <span class="relative z-1 flex size-[54px] shrink-0 flex-col items-center justify-center gap-1 overflow-hidden rounded-[10px] border border-dashed border-brand/45 bg-[color-mix(in_oklab,var(--color-brand)_12%,rgba(10,8,6,0.85))] transition-all duration-400 ease-out-ex group-hover:bg-[color-mix(in_oklab,var(--color-brand)_18%,rgba(10,8,6,0.85))] group-hover:shadow-[0_4px_16px_rgba(0,0,0,0.55),0_0_0_2px_color-mix(in_oklab,var(--color-brand)_60%,transparent)] max-md:size-10">
                    <span class="font-sans text-[16px] leading-none font-bold tracking-[0.02em] text-brand">+{{ $more }}</span>
                    <span class="font-sans text-[9px] leading-none font-normal tracking-[0.05em] text-white/60 uppercase rtl:font-arabic">{{ __('more') }}</span>
                </span>
            </a>
        @endif
    </aside>

    {{-- Stage --}}
    <div data-stage class="absolute inset-0 overflow-hidden">
        <div data-swiper class="swiper absolute! inset-0 overflow-hidden" aria-live="off">
            <div class="swiper-wrapper relative block! h-full w-full transform-none!">
                @foreach ($slides as $i => $slide)
                    <div
                        data-slide="{{ $i }}"
                        @class(['swiper-slide absolute! inset-0! h-full! w-full! overflow-hidden will-change-[clip-path,transform]', 'z-2' => $i === 0, 'invisible hidden' => $i > 0])
                        role="tabpanel"
                        aria-label="{{ $slide['title'] }}"
                        aria-roledescription="slide"
                    >
                        {{-- Only the first photo loads with the page; the others once it has loaded (hero.js). --}}
                        <div data-bg class="absolute inset-x-0 -top-[5%] h-[110%] w-full bg-cover bg-center will-change-transform" @if ($i === 0) style="background-image: url('{{ $slide['image'] }}')" @else data-bg-src="{{ $slide['image'] }}" @endif aria-hidden="true"></div>
                        <div class="absolute inset-0 bg-[linear-gradient(105deg,rgba(5,5,5,0.72)_0%,rgba(5,5,5,0.38)_44%,rgba(5,5,5,0.08)_75%,rgba(5,5,5,0)_100%),linear-gradient(rgba(5,5,5,0.45)_0%,rgba(5,5,5,0)_22%),linear-gradient(0deg,rgba(5,5,5,0.55)_0%,rgba(5,5,5,0)_40%)] rtl:bg-[linear-gradient(255deg,rgba(5,5,5,0.72)_0%,rgba(5,5,5,0.38)_44%,rgba(5,5,5,0.08)_75%,rgba(5,5,5,0)_100%),linear-gradient(rgba(5,5,5,0.45)_0%,rgba(5,5,5,0)_22%),linear-gradient(0deg,rgba(5,5,5,0.55)_0%,rgba(5,5,5,0)_40%)]" aria-hidden="true"></div>
                        <div class="absolute start-[calc(124px+48px)] end-12 top-1/2 z-5 max-w-none -translate-y-1/2 rtl:end-auto max-md:inset-x-5">
                            <div data-text class="mb-[22px] flex items-center gap-[14px] will-change-[transform,opacity,filter]">
                                <span class="font-sans text-[10px] font-medium tracking-[0.26em] text-brand-lt uppercase rtl:font-arabic">{{ __('Exclusive Property') }}</span>
                            </div>
                            <h2 data-text class="mb-[14px] font-sans text-[clamp(40px,5.2vw,76px)] leading-[1.02] font-light tracking-[-0.02em] text-paper will-change-[transform,opacity,filter] rtl:font-arabic max-md:text-[34px]">{{ $slide['title'] }}</h2>
                            @if ($slide['tagline'])
                                <p data-text class="mb-[14px] font-sans text-[clamp(17px,1.7vw,22px)] font-light tracking-[0.01em] text-white/68 italic will-change-[transform,opacity,filter] rtl:font-arabic">{{ $slide['tagline'] }}</p>
                            @endif
                            @if ($slide['location'])
                                <div data-text class="mb-[38px] inline-flex items-center gap-[7px] will-change-[transform,opacity,filter]">
                                    <x-website.icons.pin class="size-[13px] shrink-0 text-paper opacity-55" />
                                    <span class="font-sans text-[10px] font-medium tracking-[0.18em] text-white/55 uppercase rtl:font-arabic">{{ $slide['location'] }}</span>
                                </div>
                            @endif
                            <div data-text data-ctas class="flex items-center gap-[14px] will-change-[transform,opacity,filter]">
                                <x-website.button :href="route('projects')" arrow>{{ __('Explore Projects') }}</x-website.button>
                                <x-website.button :href="$slide['url']" variant="ghost" aria-label="{{ __('View details of :title', ['title' => $slide['title']]) }}">{{ __('View Project Details') }}</x-website.button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div data-announcer class="sr-only" aria-live="polite" aria-atomic="true"></div>

        {{-- Glass badge --}}
        <div data-glass-tag class="absolute top-[calc(var(--spacing-nav)+20px)] right-11 z-40 flex items-center gap-2 rounded-full border border-glass-border bg-glass px-4 py-2 backdrop-blur-[20px] backdrop-saturate-180 rtl:right-auto rtl:left-11 max-md:top-[calc(var(--spacing-nav)+12px)] max-md:ltr:right-4" aria-hidden="true">
            <div class="size-1.5 animate-pulse-dot rounded-full bg-brand"></div>
            <span class="font-sans text-[9.5px] font-medium tracking-[0.16em] text-ink-60 uppercase rtl:font-arabic">{{ __('Exclusive Collection :year', ['year' => now()->year]) }}</span>
        </div>

        {{-- Prev → progress → next --}}
        <div class="absolute top-1/2 right-11 z-40 flex -translate-y-1/2 flex-col items-center gap-5 rtl:right-auto rtl:left-11">
            <button type="button" data-nav-btn @click="prev()" class="{{ $navButton }}" aria-label="{{ __('Previous property') }}">
                <span class="absolute -inset-[14px]"></span>
                <svg width="16" height="14" viewBox="0 0 16 14" fill="none" aria-hidden="true" class="size-[18px] transition-[translate,color] duration-250 group-hover:-translate-x-0.5 rtl:-scale-x-100">
                    <g transform="scale(-1,1) translate(-16,0)">
                        <path d="M7.99997 0.414307C7.99997 1.11051 8.68991 2.15231 9.38737 3.02724C10.2855 4.15371 11.3574 5.13777 12.5873 5.88911C13.5087 6.45171 14.6276 6.99177 15.5264 6.99177M7.99997 13.5855C7.99997 12.8893 8.68991 11.8475 9.38737 10.9726C10.2855 9.84617 11.3574 8.86204 12.5873 8.11071C13.5087 7.54811 14.6276 7.00804 15.5264 7.00804M15.5264 6.99991H0.473572" stroke="currentColor" />
                    </g>
                </svg>
            </button>

            <div class="flex flex-col items-center gap-[10px] select-none" role="status" aria-label="{{ __('Slide progress') }}">
                <span data-prog data-prog-current class="font-sans text-[13px] leading-none font-light tracking-[0.06em] text-paper tabular-nums">01</span>
                <div data-prog class="relative h-[110px] w-px overflow-hidden bg-white/20">
                    <div data-prog-fill class="absolute inset-0 origin-top bg-brand will-change-transform [transform:scaleY(0)]"></div>
                </div>
                <span data-prog class="font-sans text-[11px] leading-none font-light tracking-[0.06em] text-white/35 tabular-nums">{{ str_pad((string) count($slides), 2, '0', STR_PAD_LEFT) }}</span>
            </div>

            <button type="button" data-nav-btn @click="next()" class="{{ $navButton }}" aria-label="{{ __('Next property') }}">
                <span class="absolute -inset-[14px]"></span>
                <x-website.icons.arrow class="size-[18px] transition-[translate,color] duration-250 group-hover:translate-x-0.5 rtl:-scale-x-100" />
            </button>
        </div>
    </div>
</section>
