{{--
    Property details (the design's property-details.html): breadcrumb, hero (details + photo slider), sticky bar,
    content (overview, stats, description, details, amenities, nearby facilities, map, master plan) with the price
    and payment-plan sidebar, and similar properties. Behaviour: resources/js/website/property.js.
    @var array $property  App\Support\Website\PropertyDetails::for()
    @var list<array> $similar  App\Support\Website\PropertyCards::from()
--}}
@php
    $business = app(\Modules\GeneralSettings\Settings\BusinessInfoSettings::class);
    $p = $property;
    $count = count($p['images']);
    $whatsapp = $business->whatsapp ? 'https://wa.me/'.preg_replace('/\D/', '', $business->whatsapp) : null;
    $compare = ['id' => $p['id'], 'slug' => $slug, 'title' => $p['title'], 'thumb' => $p['thumbs'][0] ?? null];
    $badge = 'inline-flex items-center gap-1 rounded px-[10px] py-1 text-[10px] leading-none font-bold tracking-[0.12em] uppercase rtl:font-arabic';
    $dark = 'bg-ink text-paper dark:border dark:border-[rgba(237,232,223,0.18)] dark:bg-paper dark:text-black';
    $outline = 'border-[1.5px] border-ink-60 text-ink';
    $sectionTitle = 'mb-5 border-l-[3px] border-brand pl-[14px] font-sans text-[11px] leading-[1.2] font-bold tracking-[0.14em] text-ink uppercase rtl:border-r-[3px] rtl:border-l-0 rtl:pr-[14px] rtl:pl-0 rtl:font-arabic';
    $sep = 'mb-10 h-px bg-[rgba(200,16,46,0.2)] dark:bg-line';
    $tile = 'flex items-center gap-[10px] rounded-lg border border-ink-10 bg-paper p-3 transition-[border-color] duration-200 hover:border-brand-lt dark:border-line dark:bg-surface-2 dark:hover:border-[rgba(212,20,44,0.35)] dark:hover:bg-surface-3';
    $stickyButton = 'relative flex size-[34px] cursor-pointer items-center justify-center rounded-full border border-white/20 bg-white/6 text-white/70 transition-[background,border-color,color] duration-200 hover:border-brand hover:bg-[rgba(200,16,46,0.15)]';
    $schedule = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
    $pdfIcon = '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/>';
    $heart = '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>';
    $pin = '<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>';
    $icons = [
        'area' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/>',
        'beds' => '<path d="M2 9v11"/><path d="M22 9v11"/><path d="M2 19h20"/><path d="M2 13h20"/><path d="M6 13V9a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v4"/>',
        'baths' => '<path d="M9 6 6.5 3.5a1.5 1.5 0 0 0-1-.5C4.683 3 4 3.683 4 4.5V17a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5"/><line x1="10" y1="5" x2="8" y2="7"/><line x1="2" y1="12" x2="22" y2="12"/>',
        'parking' => '<rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 4v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
        'floors' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
    ];
    $stats = array_filter([
        'beds' => $p['beds'] !== null ? [$p['beds'], __('Bedrooms')] : null,
        'baths' => $p['baths'] !== null ? [$p['baths'], __('Bathrooms')] : null,
        'area' => $p['area'] !== null ? [$p['area'].' '.__('m²'), __('Total Area')] : null,
        'parking' => $p['parking'] !== null ? [$p['parking'], __('Parking')] : null,
        'floors' => $p['floors'] !== null ? [$p['floors'], __('Floors')] : null,
    ]);
    $details = array_chunk($p['details'], 2, true);
@endphp

<x-website.layout :title="$p['title']" :description="$p['short']">
    <div
        x-data="propertyPage(@js(['count' => $count, 'latitude' => $p['latitude'], 'longitude' => $p['longitude'], 'place' => $p['place']]))"
        data-pdf-phone="{{ $business->hotline }}"
        data-pdf-email="{{ $business->email }}"
        class="leading-[normal]"
    >
        {{-- Breadcrumb --}}
        <nav x-ref="crumb" class="mt-nav flex h-11 items-center border-b border-ink-10 bg-white px-12 dark:border-line dark:bg-surface-1 max-[900px]:px-6" aria-label="{{ __('Breadcrumb') }}">
            <ol class="flex list-none items-center gap-1.5 text-[12px] text-ink-60">
                <li><a href="{{ route('home') }}" class="text-ink-60 transition-colors duration-200 hover:text-brand dark:hover:text-ink">{{ __('Home') }}</a></li>
                <li><span class="mx-1 text-[10px] text-ink-30" aria-hidden="true"><span class="rtl:hidden">›</span><span class="ltr:hidden">‹</span></span><a href="{{ route('properties') }}" class="text-ink-60 transition-colors duration-200 hover:text-brand dark:hover:text-ink">{{ __('Properties') }}</a></li>
                <li class="font-medium text-ink"><span class="mx-1 text-[10px] text-ink-30" aria-hidden="true"><span class="rtl:hidden">›</span><span class="ltr:hidden">‹</span></span>{{ $p['title'] }}</li>
            </ol>
        </nav>

        {{-- Hero: details + photos --}}
        <section x-ref="hero" class="grid min-h-[calc(100vh-var(--spacing-nav)-44px)] grid-cols-[38fr_62fr] items-start bg-page max-[960px]:grid-cols-1">
            <div class="flex flex-col py-[52px] pr-11 pl-[52px] *:animate-fade-up rtl:pr-[52px] rtl:pl-11 max-[960px]:px-6! max-[960px]:pt-9! max-[960px]:pb-7! [&>*:nth-child(1)]:[animation-delay:0.08s] [&>*:nth-child(2)]:[animation-delay:0.15s] [&>*:nth-child(3)]:[animation-delay:0.21s] [&>*:nth-child(4)]:[animation-delay:0.27s] [&>*:nth-child(5)]:[animation-delay:0.33s] [&>*:nth-child(6)]:[animation-delay:0.39s] [&>*:nth-child(7)]:[animation-delay:0.44s]">
                <div class="mb-5 flex flex-wrap items-center gap-[14px]">
                    @if ($p['year'])
                        <span class="text-[11px] font-medium tracking-[0.14em] text-ink-60">{{ __('Delivery :year', ['year' => $p['year']]) }}</span>
                    @endif
                    @if ($p['featured'])
                        <span class="{{ $badge }} {{ $dark }}">{{ __('Featured') }}</span>
                    @endif
                    @if ($p['offering'])
                        <span class="{{ $badge }} {{ $outline }}">{{ $p['offering'] }}</span>
                    @endif
                </div>
                <h1 class="mb-[14px] font-sans text-[clamp(24px,2.6vw,36px)] leading-[1.2] font-bold tracking-[-0.01em] text-ink rtl:font-arabic max-[960px]:text-[clamp(22px,5vw,32px)]">{{ $p['title'] }}</h1>
                @if ($p['developer'])
                    <div class="mb-[18px] flex items-center gap-[10px]">
                        <div class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-brand bg-ink">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-[17px] text-brand" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" /><polyline points="9 22 9 12 15 12 15 22" /></svg>
                        </div>
                        <div>
                            <p class="mb-0.5 text-[9px] leading-none font-medium tracking-[0.2em] text-ink-30 uppercase rtl:font-arabic rtl:tracking-[0.04em] rtl:normal-case">{{ __('Developer') }}</p>
                            <p class="text-[12px] font-semibold tracking-[0.01em] text-ink-60">{{ $p['developer'] }}</p>
                        </div>
                    </div>
                @endif
                @if ($p['place'])
                    <p class="mb-7 flex items-center gap-1.5 text-[12px] tracking-[0.02em] text-ink-60">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" class="shrink-0 text-brand" aria-hidden="true">{!! $pin !!}</svg>
                        {{ $p['place'] }}
                    </p>
                @endif
                <div class="mb-7 flex flex-wrap gap-[10px] max-[560px]:gap-2">
                    @foreach (['area' => $p['area'] !== null ? $p['area'].' '.__('m²') : null, 'beds' => $p['beds'], 'baths' => $p['baths']] as $key => $value)
                        @continue($value === null)
                        <div class="flex items-center gap-2 rounded-md border border-ink-10 bg-paper px-4 py-3 text-[13px] font-medium whitespace-nowrap text-ink dark:border-line dark:bg-surface-2 max-[560px]:px-3 max-[560px]:py-[10px] max-[560px]:text-[12px]">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 text-brand" aria-hidden="true">{!! $icons[$key] !!}</svg>
                            @if ($key === 'area')
                                <span class="mr-0.5 text-[11px] font-normal text-ink-60 rtl:mr-0 rtl:ml-0.5">{{ __('Area') }}</span>
                            @endif
                            <span class="font-bold tracking-[-0.01em]">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
                @if ($p['price'])
                    <div data-pdf-price class="mb-[18px] font-sans text-[clamp(32px,3.2vw,48px)] leading-none font-semibold tracking-[-0.01em] text-brand-dk"><sup class="mr-1 align-super text-[0.45em] font-normal tracking-[0.06em] text-ink-60 rtl:mr-0 rtl:ml-1">{{ __('EGP') }}</sup>{{ $p['price'] }}</div>
                @endif
                <div class="flex flex-wrap items-center gap-3 rtl:flex-row-reverse">
                    <button
                        type="button"
                        @click="pdf()"
                        :disabled="pdfLoading"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-brand bg-brand px-[26px] py-[13px] font-sans text-[13px] leading-none font-[650] tracking-[0.055em] text-white backdrop-blur-[12px] transition-all duration-300 hover:-translate-y-0.5 hover:border-[rgb(165,13,38)] hover:bg-[rgb(165,13,38)] dark:border-white/30 dark:bg-white/10 dark:hover:border-white/70 dark:hover:bg-white/20 rtl:font-arabic"
                        :class="pdfLoading && 'pointer-events-none opacity-70'"
                        aria-label="{{ __('Download property PDF') }}"
                    >
                        <span x-show="pdfLoading" class="size-[13px] animate-spin rounded-full border-2 border-white border-x-white/35 border-b-white/35 [animation-duration:0.7s]" aria-hidden="true"></span>
                        <svg x-show="! pdfLoading" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $pdfIcon !!}</svg>
                        <span>{{ __('Download PDF') }}</span>
                    </button>
                    @if ($business->hotline)
                        <a
                            href="tel:{{ $business->hotline }}"
                            @click="$track('phone', { unit_id: {{ $p['id'] }} })"
                            class="inline-flex cursor-pointer items-center gap-2 rounded-full border-[1.5px] border-white/65 bg-white/48 px-[26px] py-[13px] font-sans text-[13px] leading-none font-[650] tracking-[0.055em] whitespace-nowrap text-brand-dk shadow-[0_2px_16px_rgba(0,0,0,0.08),0_1px_4px_rgba(0,0,0,0.05)] backdrop-blur-[20px] backdrop-saturate-200 transition-all duration-350 ease-out-ex hover:-translate-y-0.5 dark:border-white/28 dark:bg-transparent dark:text-white dark:shadow-none dark:backdrop-blur-none dark:hover:border-white/48 dark:hover:bg-white/8 rtl:font-arabic [&_svg]:rtl:-scale-x-100"
                        >{!! $schedule !!}<span>{{ __('Schedule a Visit') }}</span></a>
                    @endif
                </div>
            </div>

            <div class="flex animate-slide-in flex-col py-9 pr-[52px] pl-0 rtl:pr-0 rtl:pl-[52px] max-[960px]:px-6! max-[960px]:pt-0! max-[960px]:pb-10!">
                <div x-ref="gallery" class="relative aspect-[16/10] overflow-hidden rounded-xl bg-[rgb(17,17,17)] select-none">
                    @forelse ($p['images'] as $i => $image)
                        {{-- Only the first photo loads with the page (first, as the page's biggest thing); the next ones as
                             the slider reaches them (property.js). Phones take the card size. --}}
                        <img
                            @if ($i === 0)
                                src="{{ $image }}" srcset="{{ $p['srcsets'][0] }}" fetchpriority="high" data-pdf-hero
                            @else
                                data-src="{{ $image }}" data-srcset="{{ $p['srcsets'][$i] }}"
                            @endif
                            sizes="(max-width: 960px) 100vw, 62vw"
                            data-photo="{{ $i }}"
                            alt="{{ $p['title'] }}"
                            class="pointer-events-none absolute inset-0 size-full object-cover transition-opacity duration-600 ease-out-ex"
                            :class="{ 'z-1 opacity-100': current === {{ $i }}, 'opacity-0': current !== {{ $i }} }"
                        />
                    @empty
                        <img src="{{ \App\Support\Website\Image::placeholder() }}" alt="{{ $p['title'] }}" class="pointer-events-none absolute inset-0 size-full object-cover" />
                    @endforelse

                    <div class="absolute bottom-[54px] left-4 z-20 flex gap-2 rtl:right-4 rtl:left-auto">
                        <button type="button" @click="$store.compare.toggle(@js($compare))" class="flex size-[34px] cursor-pointer items-center justify-center rounded-full border-[1.5px] border-white/40 bg-white/15 backdrop-blur-[10px] transition-[background,scale] duration-200 hover:scale-108 hover:bg-white/28" title="{{ __('Compare') }}" aria-label="{{ __('Compare') }}">
                            <x-website.icons.compare width="15" height="15" class="text-white" />
                        </button>
                        <button type="button" @click="$store.favourites.toggle({{ $p['id'] }})" class="flex size-[34px] cursor-pointer items-center justify-center rounded-full border-none bg-brand transition-[background,scale] duration-200 hover:scale-108 hover:bg-brand-dk" title="{{ __('Save') }}" aria-label="{{ __('Save to favourites') }}">
                            <svg viewBox="0 0 24 24" :fill="$store.favourites.has({{ $p['id'] }}) ? 'currentColor' : 'none'" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-[15px] text-white" aria-hidden="true">{!! $heart !!}</svg>
                        </button>
                    </div>

                    @if ($count)
                        <button type="button" @click="$store.projectLightbox.show(@js($p['images']), @js($p['title'])); $store.projectLightbox.index = current" class="absolute right-4 bottom-[54px] z-20 flex cursor-pointer items-center gap-2 rounded-[20px] bg-black/55 px-3 py-[5px] text-[11px] font-medium tracking-[0.06em] text-white/90 backdrop-blur-[10px] transition-[background,scale] duration-200 hover:scale-104 hover:bg-black/78" aria-label="{{ __('Open gallery fullscreen') }}">
                            <span x-text="current + 1">1</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-[14px]" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" /><circle cx="12" cy="13" r="4" /></svg>
                            <span>{{ $count }}</span>
                        </button>
                    @endif

                    @if ($count > 1)
                        @php($arrow = 'flex size-[34px] cursor-pointer items-center justify-center rounded-full border border-[rgba(13,13,13,0.15)] bg-[rgba(13,13,13,0.72)] text-white backdrop-blur-[12px] transition-[background,scale] duration-200 hover:scale-108 hover:bg-ink')
                        <div class="absolute bottom-4 left-1/2 z-20 flex -translate-x-1/2 items-center gap-3">
                            <button type="button" @click="go(current - 1)" class="{{ $arrow }}" aria-label="{{ __('Previous image') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="size-[13px] rtl:-scale-x-100" aria-hidden="true"><polyline points="15 18 9 12 15 6" /></svg>
                            </button>
                            <div class="flex items-center gap-[7px] rounded-[20px] bg-[rgba(13,13,13,0.65)] px-3 py-[7px] backdrop-blur-[12px]">
                                @foreach ($p['images'] as $i => $image)
                                    <button type="button" @click="go({{ $i }})" class="h-[7px] cursor-pointer rounded-full border-none p-0 transition-[background,scale,width] duration-300" :class="current === {{ $i }} ? 'w-[18px] scale-120 rounded bg-brand' : 'w-[7px] bg-white/35'" aria-label="{{ __('Slide :number', ['number' => $i + 1]) }}"></button>
                                @endforeach
                            </div>
                            <button type="button" @click="go(current + 1)" class="{{ $arrow }}" aria-label="{{ __('Next image') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="size-[13px] rtl:-scale-x-100" aria-hidden="true"><polyline points="9 18 15 12 9 6" /></svg>
                            </button>
                        </div>
                    @endif
                </div>

                @if ($count)
                    <div class="mt-3 flex gap-[10px] max-[900px]:flex-nowrap max-[900px]:overflow-x-auto">
                        <button type="button" @click="$store.projectLightbox.show(@js($p['images']), @js($p['title']))" class="flex min-w-20 shrink-0 cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border border-ink-10 bg-paper px-[10px] py-3 transition-[border-color] duration-200 hover:border-brand dark:border-line dark:bg-surface-2 dark:hover:border-line-md">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="text-brand-dk" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" /><polyline points="9 22 9 12 15 12 15 22" /></svg>
                            <span class="text-[20px] leading-none font-bold tracking-[-0.02em] text-ink">{{ $count }}</span>
                            <span class="text-[9px] font-medium tracking-[0.16em] text-ink-60 uppercase rtl:font-arabic">{{ __('Photos') }}</span>
                        </button>
                        @foreach (array_slice($p['thumbs'], 0, 4) as $i => $thumb)
                            <button type="button" @click="go({{ $i }})" class="group/thumb relative h-[90px] flex-1 cursor-pointer overflow-hidden rounded-xl border-[3px] transition-[border-color] duration-250" :class="current === {{ $i }} ? 'border-brand' : 'border-white dark:border-surface-2'">
                                <img src="{{ $thumb }}" alt="" loading="lazy" class="size-full object-cover transition-transform duration-400 ease-out-ex group-hover/thumb:scale-106" />
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- Sticky bar --}}
        <div
            class="pointer-events-none fixed inset-x-0 top-nav z-190 flex h-[52px] -translate-y-full items-center justify-between border-b border-white/6 bg-[rgba(8,8,8,0.92)] px-12 opacity-0 backdrop-blur-[48px] backdrop-saturate-180 [transition:translate_0.4s_var(--ease-out-ex),opacity_0.4s_var(--ease-out-ex)] max-[900px]:px-6"
            :class="{ 'pointer-events-auto translate-y-0 opacity-100': sticky, 'pointer-events-none -translate-y-full opacity-0': ! sticky }"
            :aria-hidden="! sticky"
        >
            <div class="flex items-center gap-5">
                <span class="text-[13px] font-semibold tracking-[0.01em] whitespace-nowrap text-white/90 max-[640px]:max-w-[140px] max-[640px]:overflow-hidden max-[640px]:text-ellipsis rtl:font-arabic">{{ $p['title'] }}</span>
                @if ($p['price'])
                    <span class="font-sans text-[18px] font-medium tracking-[0.02em] text-brand max-[640px]:text-[13px]">{{ __('EGP :price', ['price' => $p['price']]) }}</span>
                @endif
            </div>
            <div class="flex items-center gap-[10px]">
                @if ($business->hotline)
                    <a href="tel:{{ $business->hotline }}" @click="$track('phone', { unit_id: {{ $p['id'] }} })" class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-[linear-gradient(135deg,rgba(215,20,52,0.96)_0%,rgb(185,10,36)_100%)] px-[18px] py-2 font-sans text-[11px] leading-none font-[650] tracking-[0.06em] whitespace-nowrap text-white backdrop-blur-[20px] transition-all duration-350 ease-out-ex hover:-translate-y-0.5 hover:bg-[linear-gradient(135deg,rgba(225,25,58,0.98)_0%,rgb(200,16,46)_100%)] rtl:font-arabic">{{ __('Schedule Visit') }}</a>
                @endif
                <button type="button" @click="$store.compare.toggle(@js($compare))" class="{{ $stickyButton }}" :class="$store.compare.has({{ $p['id'] }}) ? 'border-brand! bg-[rgba(200,16,46,0.15)]! text-brand!' : 'hover:text-brand'" title="{{ __('Compare') }}" aria-label="{{ __('Compare property') }}">
                    <x-website.icons.compare width="15" height="15" />
                </button>
                <button type="button" @click="pdf()" :disabled="pdfLoading" class="{{ $stickyButton }} hover:text-white" title="{{ __('Download PDF') }}" aria-label="{{ __('Download property PDF') }}">
                    <span x-show="pdfLoading" class="size-[13px] animate-spin rounded-full border-2 border-white border-x-white/30 border-b-white/30 [animation-duration:0.7s]" aria-hidden="true"></span>
                    <svg x-show="! pdfLoading" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-[15px]" aria-hidden="true">{!! $pdfIcon !!}</svg>
                </button>
                <button type="button" @click="$store.favourites.toggle({{ $p['id'] }})" class="{{ $stickyButton }}" :class="$store.favourites.has({{ $p['id'] }}) ? 'border-brand! bg-[rgba(200,16,46,0.15)]! text-brand!' : 'hover:text-brand'" title="{{ __('Save') }}" aria-label="{{ __('Save property') }}">
                    <svg viewBox="0 0 24 24" :fill="$store.favourites.has({{ $p['id'] }}) ? 'currentColor' : 'none'" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-[15px]" aria-hidden="true">{!! $heart !!}</svg>
                </button>
            </div>
            <div class="absolute bottom-0 left-0 h-0.5 bg-brand transition-[width] duration-100 ease-linear" :style="`width: ${progress}%`"></div>
        </div>

        {{-- Content + sidebar --}}
        <main class="bg-page px-12 pt-12 pb-20 max-[900px]:px-6 max-[900px]:pt-8 max-[900px]:pb-[60px]">
            <div class="mx-auto grid max-w-[1280px] grid-cols-[1fr_340px] items-start gap-10 max-[900px]:grid-cols-1">
                <div class="min-w-0">
                    <div class="mb-10">
                        <div data-pdf-badges class="mb-[14px] flex flex-wrap items-center gap-2">
                            @if ($p['featured'])
                                <span class="{{ $badge }} {{ $dark }}">{{ __('Featured') }}</span>
                            @endif
                            @if ($p['offering'])
                                <span class="{{ $badge }} {{ $outline }}">{{ $p['offering'] }}</span>
                            @endif
                            @if ($p['finishing'])
                                <span class="{{ $badge }} border-[1.5px] border-brand bg-brand text-white">{{ $p['finishing'] }}</span>
                            @endif
                        </div>
                        <h1 data-pdf-name class="mb-3 font-sans text-[38px] leading-[1.15] font-normal tracking-[-0.01em] text-ink rtl:font-arabic max-[900px]:text-[30px] max-[640px]:text-[24px]">{{ $p['title'] }}</h1>
                        @if ($p['place'])
                            <p data-pdf-location class="mb-4 flex items-center gap-1.5 text-[13px] text-ink-60">
                                <svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14" class="shrink-0 text-brand" aria-hidden="true">{!! $pin !!}</svg>
                                {{ $p['place'] }}
                            </p>
                        @endif
                        <div class="flex flex-wrap items-center gap-6">
                            @if ($p['views'])
                                <span class="text-[11px] tracking-[0.04em] text-ink-60">{{ __('Views') }}: <strong class="font-semibold text-ink">{{ implode(' & ', $p['views']) }}</strong></span>
                            @endif
                            @if ($p['updated'])
                                <span class="text-[11px] tracking-[0.04em] text-ink-60">{{ __('Updated') }}: <strong class="font-semibold text-ink">{{ $p['updated'] }}</strong></span>
                            @endif
                        </div>
                    </div>

                    @if ($stats)
                        <div class="mb-10 flex items-stretch overflow-hidden rounded-xl border border-ink-10 bg-paper dark:border-line dark:bg-surface-2 max-[900px]:flex-wrap">
                            @foreach ($stats as $key => [$value, $label])
                                <div data-pdf-stat class="relative flex flex-1 flex-col items-center justify-center gap-1.5 px-3 py-5 not-first:before:absolute not-first:before:top-[20%] not-first:before:left-0 not-first:before:h-[60%] not-first:before:w-px not-first:before:bg-ink-10 rtl:not-first:before:right-0 rtl:not-first:before:left-auto max-[900px]:min-w-[calc(50%-1px)] max-[640px]:min-w-full max-[640px]:not-first:before:hidden">
                                    <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-white/12 text-brand backdrop-blur-[8px]">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-[17px]" aria-hidden="true">{!! $icons[$key] !!}</svg>
                                    </div>
                                    <span data-pdf-val class="text-[18px] leading-none font-bold tracking-[-0.02em] text-ink">{{ $value }}</span>
                                    <span data-pdf-label class="text-[10px] font-medium tracking-[0.1em] text-ink-60 uppercase rtl:font-arabic">{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($p['intro'])
                        <div data-pdf-desc class="mb-10">
                            <div class="{{ $sectionTitle }}">{{ __('Overview') }}</div>
                            <div class="[&_p]:mb-4 [&_p]:text-[16px] [&_p]:leading-[1.8] [&_p]:text-ink-60">{!! $p['intro'] !!}</div>
                            @if ($p['more'])
                                <div x-show="more" x-cloak style="display: none" class="[&_p]:mb-4 [&_p]:text-[16px] [&_p]:leading-[1.8] [&_p]:text-ink-60">{!! $p['more'] !!}</div>
                                <button type="button" @click="more = ! more" class="inline-flex cursor-pointer items-center gap-1.5 border-none bg-none p-0 text-[13px] font-semibold tracking-[0.02em] text-brand underline underline-offset-3 transition-colors duration-200 hover:text-brand-dk">
                                    <span x-text="more ? @js(__('Read Less')) : @js(__('Read More'))">{{ __('Read More') }}</span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-[14px] transition-[rotate] duration-300 ease-smooth" :class="more && 'rotate-180'" aria-hidden="true"><polyline points="6 9 12 15 18 9" /></svg>
                                </button>
                            @endif
                        </div>
                        <div class="{{ $sep }}"></div>
                    @endif

                    @if ($details)
                        <div class="mb-10">
                            <div class="{{ $sectionTitle }}">{{ __('Property Details') }}</div>
                            <div class="mb-10 grid grid-cols-2 overflow-hidden rounded-xl border border-ink-10 dark:border-line max-[640px]:grid-cols-1">
                                @foreach ($details as $row => $cells)
                                    @foreach ($cells as $label => $value)
                                        <div data-pdf-detail @class([
                                            'flex flex-col gap-0.5 px-[18px] py-[13px]',
                                            'border-b border-ink-10 dark:border-line' => $row < count($details) - 1,
                                            'bg-[rgba(13,13,13,0.02)] dark:bg-[rgba(237,232,223,0.03)]' => $row % 2 === 0,
                                        ])>
                                            <span data-pdf-label class="text-[10px] font-semibold tracking-[0.12em] text-ink-60 uppercase rtl:font-arabic">{{ $label }}</span>
                                            <span data-pdf-val class="text-[14px] font-semibold text-ink">{{ $value }}</span>
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                        <div class="{{ $sep }}"></div>
                    @endif

                    @if ($p['amenities'])
                        <div class="mb-10">
                            <div class="{{ $sectionTitle }}">{{ __('Amenities') }}</div>
                            <div class="mb-10 grid grid-cols-3 gap-[10px] max-[900px]:grid-cols-2 max-[640px]:grid-cols-1">
                                @foreach ($p['amenities'] as $amenity)
                                    <div class="{{ $tile }}">
                                        <div class="flex size-9 shrink-0 items-center justify-center rounded-md bg-white/12 text-brand backdrop-blur-[8px] dark:bg-[rgba(212,20,44,0.1)]">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-[17px]" aria-hidden="true"><polyline points="20 6 9 17 4 12" /></svg>
                                        </div>
                                        <span data-pdf-amenity class="text-[12px] leading-[1.3] font-medium text-ink dark:text-ink-60">{{ $amenity }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="{{ $sep }}"></div>
                    @endif

                    @if ($p['facilities'])
                        <div class="mb-10">
                            <div class="{{ $sectionTitle }}">{{ __('Nearby Facilities') }}</div>
                            <div class="mb-10 grid grid-cols-2 gap-[10px] max-[640px]:grid-cols-1">
                                @foreach ($p['facilities'] as $facility)
                                    <div class="{{ $tile }}">
                                        <div class="flex size-9 shrink-0 items-center justify-center rounded-md bg-[rgba(228,201,154,0.15)] text-brand-dk">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-[17px]" aria-hidden="true">{!! $pin !!}</svg>
                                        </div>
                                        <span data-pdf-facility class="text-[12px] font-medium text-ink dark:text-ink-60">{{ $facility }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="{{ $sep }}"></div>
                    @endif

                    @if ($p['latitude'] && $p['longitude'])
                        <div class="mb-10">
                            <div class="{{ $sectionTitle }}">{{ __('Location') }}</div>
                            <div class="mb-[10px] flex items-center gap-1.5 text-[11px] font-semibold tracking-[0.1em] text-ink-60 uppercase">
                                <svg viewBox="0 0 24 24" fill="currentColor" class="size-[13px] text-brand" aria-hidden="true">{!! $pin !!}</svg>
                                {{ $p['place'] }}
                            </div>
                            <div x-ref="map" x-intersect.once.margin.300px="map()" class="relative z-0 h-[380px] w-full overflow-hidden rounded-xl border border-ink-10 max-[900px]:h-[260px] max-[640px]:h-[220px]"></div>
                        </div>
                        <div class="{{ $sep }}"></div>
                    @endif

                    @foreach ([['floorPlan', __('Floor Plan'), __('View floor plan fullscreen')], ['masterPlan', __('Master Plan'), __('View master plan fullscreen')]] as [$plan, $planTitle, $planFullscreen])
                        @continue(! $p[$plan])
                        <div class="mb-10">
                            <div class="{{ $sectionTitle }}">{{ $planTitle }}</div>
                            <div class="relative mb-10">
                                <img src="{{ $p[$plan] }}" alt="{{ $planTitle }}" loading="lazy" class="block h-[320px] w-full rounded-xl border border-ink-10 object-cover max-[900px]:h-[220px] max-[640px]:h-[180px]" />
                                <button type="button" @click="$store.projectLightbox.show([@js($p[$plan])], @js($planTitle))" class="absolute right-4 bottom-4 flex size-10 cursor-pointer items-center justify-center rounded-lg border border-white/15 bg-[rgba(8,8,8,0.75)] text-white/85 backdrop-blur-[12px] transition-[background,scale] duration-200 hover:scale-106 hover:bg-[rgba(8,8,8,0.92)]" aria-label="{{ $planFullscreen }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-[18px]" aria-hidden="true"><polyline points="15 3 21 3 21 9" /><polyline points="9 21 3 21 3 15" /><line x1="21" y1="3" x2="14" y2="10" /><line x1="3" y1="21" x2="10" y2="14" /></svg>
                                </button>
                            </div>
                        </div>
                        @if ($plan === 'floorPlan' && $p['masterPlan'])
                            <div class="{{ $sep }}"></div>
                        @endif
                    @endforeach
                </div>

                <aside class="sticky top-[calc(var(--spacing-nav)+70px)] flex flex-col gap-4 self-start max-[900px]:static">
                    <div class="overflow-hidden rounded-[14px] bg-[#0d0d0d] p-7 text-white dark:border dark:border-[rgba(212,20,44,0.28)] dark:bg-[linear-gradient(145deg,rgb(26,16,7)_0%,rgb(18,12,6)_100%)] dark:shadow-[0_0_0_1px_rgba(212,20,44,0.08),0_12px_48px_rgba(0,0,0,0.6),inset_0_0_80px_rgba(212,20,44,0.06)]">
                        <p class="mb-1.5 text-[9px] font-semibold tracking-[0.2em] text-white/45 uppercase dark:text-[rgba(237,232,223,0.45)] rtl:font-arabic">{{ __('Starting Price') }}</p>
                        <div class="mb-1 font-sans text-[42px] leading-none font-normal tracking-[-0.01em] text-white dark:text-ink max-[900px]:text-[28px] max-[640px]:text-[24px]"><sup class="align-super text-[0.4em] font-normal tracking-[0.06em] text-white/60">{{ __('EGP') }}</sup>{{ $p['price'] ?? '—' }}</div>
                        <p class="mb-6 text-[11px] tracking-[0.04em] text-white/40 dark:text-ink-60">
                            @if ($p['perMeter'])
                                {{ __('EGP :price / m²', ['price' => $p['perMeter']]) }}
                            @endif
                        </p>
                        <div class="mb-5 h-px bg-white/8 dark:bg-[rgba(237,232,223,0.08)]"></div>
                        @php($cardButton = 'mb-[10px] inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-full px-[26px] py-[13px] font-sans text-[13px] leading-none font-[650] tracking-[0.055em] whitespace-nowrap text-white transition-all duration-350 ease-out-ex hover:-translate-y-0.5 rtl:font-arabic')
                        @if ($business->hotline)
                            <a href="tel:{{ $business->hotline }}" @click="$track('phone', { unit_id: {{ $p['id'] }} })" class="{{ $cardButton }} border-[1.5px] border-white/30 bg-white/15 backdrop-blur-[8px] hover:border-white/50 hover:bg-white/25 dark:border-[rgba(212,20,44,0.35)] dark:bg-[rgba(212,20,44,0.2)] dark:hover:bg-[rgba(212,20,44,0.32)]">{!! $schedule !!}<span>{{ __('Schedule Viewing') }}</span></a>
                        @endif
                        {{-- when the user fill the form it will tracked --}}
                        <a href="{{ route('contact', ['property' => $slug]) }}" @click="$track('form', { unit_id: {{ $p['id'] }} })" class="{{ $cardButton }} border-[1.5px] border-white/25 bg-white/6 hover:border-white/50 hover:bg-white/13 dark:border-[rgba(237,232,223,0.14)] dark:bg-[rgba(237,232,223,0.06)] dark:hover:bg-[rgba(237,232,223,0.1)]">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" /></svg>
                            <span>{{ __('Send Enquiry') }}</span>
                        </a>
                        <div class="mb-5 grid grid-cols-2 gap-2">
                            {{-- when the user click on Call or WhatsApp it will tracked --}}
                            @if ($business->hotline)
                                <a href="tel:{{ $business->hotline }}" @click="$track('phone', { unit_id: {{ $p['id'] }} })" class="flex items-center justify-center gap-[7px] rounded-[44px] border-[1.5px] border-white/30 bg-white/15 px-2 py-[10px] text-[11px] font-semibold tracking-[0.04em] text-white transition-[background,translate] duration-200 hover:-translate-y-px hover:bg-white/25 dark:bg-[rgba(237,232,223,0.1)] dark:hover:bg-[rgba(237,232,223,0.18)] rtl:font-arabic">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-[14px] shrink-0" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.38 2 2 0 0 1 3.6 1.2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.9a16 16 0 0 0 5.92 5.92l.88-.88a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 21.2 16.9z" /></svg>
                                    <span>{{ __('Call Now') }}</span>
                                </a>
                            @endif
                            @if ($whatsapp)
                                <a href="{{ $whatsapp }}" target="_blank" rel="noopener" @click="$track('whatsapp', { unit_id: {{ $p['id'] }} })" class="flex items-center justify-center gap-[7px] rounded-[44px] bg-[rgb(37,211,102)] px-2 py-[10px] text-[11px] font-semibold tracking-[0.04em] text-white transition-[background,translate] duration-200 hover:-translate-y-px hover:bg-[rgb(32,184,87)] rtl:font-arabic">
                                    <x-website.icons.whatsapp class="size-[14px] shrink-0" />
                                    <span>{{ __('WhatsApp') }}</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    @if ($agent = $p['agent'])
                        {{-- Agent card: the unit's agent (or its project's), an admin user --}}
                        <div class="rounded-[14px] border-t-2 border-brand bg-paper p-[22px] dark:border-t-line dark:bg-surface-2">
                            <div class="mb-[18px] flex items-center gap-[14px]">
                                @if ($agent['photo'])
                                    <img src="{{ $agent['photo'] }}" alt="{{ $agent['name'] }}" loading="lazy" class="size-[58px] shrink-0 rounded-full border-2 border-brand object-cover" />
                                @else
                                    <span class="flex size-[58px] shrink-0 items-center justify-center rounded-full border-2 border-brand bg-[rgba(200,16,46,0.08)] text-[16px] font-bold text-brand" aria-hidden="true">{{ $agent['initials'] }}</span>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="mb-0.5 text-[14px] font-bold text-ink rtl:font-arabic">{{ $agent['name'] }}</p>
                                    @if ($agent['role'])
                                        <p class="mb-1.5 text-[11px] text-ink-60 rtl:font-arabic">{{ $agent['role'] }}</p>
                                    @endif
                                    @if ($agent['rating'] !== null)
                                        <div class="flex items-center gap-0.5" role="img" aria-label="{{ __('Rated :rating out of 5', ['rating' => $agent['rating']]) }}">
                                            @for ($star = 1; $star <= 5; $star++)
                                                <svg viewBox="0 0 24 24" fill="currentColor" @class(['size-3', 'text-brand' => $star <= $agent['stars'], 'text-ink-10' => $star > $agent['stars']]) aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" /></svg>
                                            @endfor
                                            @if ($agent['reviews'] !== null)
                                                <span class="ml-1 text-[10px] text-ink-60 rtl:mr-1 rtl:ml-0">({{ trans_choice(':count review|:count reviews', $agent['reviews'], ['count' => number_format($agent['reviews'])]) }})</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div @class(['grid gap-2', 'grid-cols-2' => $agent['phone'] && $agent['whatsapp']])>
                                @php($agentButton = 'flex items-center justify-center gap-1.5 rounded-lg px-2 py-[10px] text-[11px] font-semibold tracking-[0.04em] transition-[background,translate] duration-200 hover:-translate-y-px rtl:font-arabic [&_svg]:size-[13px]')
                                @if ($agent['phone'])
                                    <a href="tel:{{ $agent['phone'] }}" @click="$track('phone', { unit_id: {{ $p['id'] }} })" class="{{ $agentButton }} bg-ink text-paper hover:bg-[rgb(26,26,26)] dark:border dark:border-[rgba(237,232,223,0.18)] dark:bg-[rgba(20,17,10,0.92)] dark:text-ink dark:hover:bg-surface-3">
                                        <x-website.icons.phone />
                                        <span>{{ __('Call Agent') }}</span>
                                    </a>
                                @endif
                                @if ($agent['whatsapp'])
                                    <a href="{{ $agent['whatsapp'] }}" target="_blank" rel="noopener noreferrer" @click="$track('whatsapp', { unit_id: {{ $p['id'] }} })" class="{{ $agentButton }} border border-[rgba(37,211,102,0.3)] bg-[rgba(37,211,102,0.12)] text-[rgb(26,158,74)] hover:bg-[rgba(37,211,102,0.2)]">
                                        <x-website.icons.whatsapp />
                                        <span>{{ __('Send Message') }}</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($p['payment'])
                        <div class="rounded-[14px] border border-[rgba(200,16,46,0.3)] bg-paper p-[22px] dark:border-[rgba(212,20,44,0.2)] dark:bg-surface-2">
                            <div class="mb-[22px] flex items-center gap-[10px]">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-[18px] text-brand" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="2" /><line x1="1" y1="10" x2="23" y2="10" /></svg>
                                <span class="text-[13px] font-bold tracking-[0.02em] text-ink rtl:font-arabic">{{ __('Payment Plan') }}</span>
                            </div>
                            <div class="relative mb-4 flex flex-col">
                                @foreach ($p['payment'] as $i => $step)
                                    <div @class(['relative flex items-start gap-[14px] rtl:flex-row-reverse', 'pb-5' => ! $loop->last])>
                                        <div class="flex shrink-0 flex-col items-center">
                                            <div class="z-1 flex size-[30px] shrink-0 items-center justify-center rounded-full bg-brand text-[11px] font-bold text-white">{{ $i + 1 }}</div>
                                            @unless ($loop->last)
                                                <div class="mt-0.5 min-h-4 w-0.5 flex-1 bg-brand-lt"></div>
                                            @endunless
                                        </div>
                                        <div class="pt-1">
                                            <div class="mb-0.5 text-[20px] leading-none font-bold tracking-[-0.02em] text-ink">{{ $step['value'] }}</div>
                                            <div class="mb-0.5 text-[11px] font-semibold tracking-[0.1em] text-ink-60 uppercase rtl:font-arabic">{{ $step['name'] }}</div>
                                            <div class="text-[11px] text-ink-60">{{ $step['note'] }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <p class="border-t border-ink-10 pt-3 text-[11px] leading-[1.5] text-ink-60">{{ __('Payment schedules are subject to developer terms. Contact our team for a tailored financing plan.') }}</p>
                        </div>
                    @endif
                </aside>
            </div>
        </main>

        {{-- Similar properties --}}
        @if ($similar)
            <section class="bg-page px-12 pb-20 max-[900px]:px-6 max-[900px]:py-12">
                <div class="mx-auto mb-9 max-w-[1280px]">
                    <p class="mb-2 text-[11px] font-bold tracking-[0.14em] text-brand uppercase dark:text-ink-60 rtl:font-arabic">{{ __('You May Also Like') }}</p>
                    <h2 class="text-[32px] leading-[1.1] font-semibold text-ink rtl:font-arabic max-[900px]:text-[24px]">{{ __('Similar Properties') }}</h2>
                </div>
                <div class="mx-auto max-w-[1280px]">
                    <div x-ref="similar" class="swiper overflow-hidden">
                        <div class="swiper-wrapper">
                            @foreach ($similar as $card)
                                <div class="swiper-slide h-auto!">
                                    <x-website.property-card :card="$card" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @php($simNav = 'flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-full border border-ink-10 bg-transparent p-0 text-ink transition-all duration-300 hover:scale-106 hover:border-ink hover:bg-ink hover:text-paper [&.is-disabled]:pointer-events-none [&.is-disabled]:cursor-not-allowed [&.is-disabled]:opacity-25')
                <div class="mx-auto mt-8 flex max-w-[1280px] items-center gap-5">
                    <button type="button" x-ref="similarPrev" class="{{ $simNav }}" aria-label="{{ __('Previous') }}">
                        <x-website.icons.arrow class="size-[15px] -scale-x-100 stroke-[1.8] rtl:scale-x-100" />
                    </button>
                    <div class="h-0.5 flex-1 overflow-hidden rounded-[2px] bg-ink-10">
                        <div x-ref="similarFill" class="h-full w-0 rounded-[2px] bg-brand transition-[width] duration-250"></div>
                    </div>
                    <button type="button" x-ref="similarNext" class="{{ $simNav }}" aria-label="{{ __('Next') }}">
                        <x-website.icons.arrow class="size-[15px] stroke-[1.8] rtl:-scale-x-100" />
                    </button>
                </div>
            </section>
        @endif
    </div>
</x-website.layout>
