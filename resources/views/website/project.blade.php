{{--
    Project details (the design's project-details.html): hero (photo with parallax, status, name, developer, place,
    gallery button, key figures), overview (description, Enquire / PDF, payment metrics), unit types (tabs per type),
    amenities and location, and the contact section with the enquiry form. Parts without data aren't shown.
    Behaviour: resources/js/website/project.js.
    @var array $project  App\Support\Website\ProjectDetails::for()
--}}
@php
    $business = app(\Modules\GeneralSettings\Settings\BusinessInfoSettings::class);
    $p = $project;
    $count = count($p['images']);
    // The project's sales advisor (an admin user) when it has one; the company's numbers for what the advisor lacks.
    $agent = $p['agent'];
    $phone = ($agent['phone'] ?? null) ?: $business->hotline;
    $email = ($agent['email'] ?? null) ?: $business->email;
    $whatsappNumber = ($agent['whatsapp'] ?? null) ?: ($business->whatsapp ? 'https://wa.me/'.preg_replace('/\D/', '', $business->whatsapp) : null);
    $whatsapp = $whatsappNumber ? $whatsappNumber.'?text='.rawurlencode(__("I'm interested in :project", ['project' => $p['title']])) : null;
    $maps = 'https://maps.google.com/?q='.rawurlencode($p['latitude'] && $p['longitude'] ? $p['latitude'].','.$p['longitude'] : ($p['address'] ?: $p['title']));
    $grain = 'bg-(image:--grain) bg-size-[200px_auto]';
    $reveal = 'opacity-0 [transform:translateY(32px)] transition-[opacity,transform] duration-900 ease-out-ex [&.is-in]:opacity-100 [&.is-in]:[transform:none]';
    $intersect = 'x-intersect.once.threshold.12="$el.classList.add(\'is-in\')"';
    $eyebrow = 'block font-sans text-[10px] font-bold tracking-[0.28em] text-brand uppercase rtl:font-arabic rtl:tracking-[0.04em] dark:text-ink-60';
    $title = 'font-sans font-light tracking-[-0.01em] leading-[1.1] rtl:font-arabic rtl:leading-[1.35] [&_em]:text-brand [&_em]:not-italic';
    $line = 'before:absolute before:top-0 before:right-12 before:left-12 before:z-1 before:h-px before:bg-[linear-gradient(to_right,var(--color-brand)_0%,rgba(200,16,46,0.16)_42%,transparent_100%)] rtl:before:bg-[linear-gradient(to_left,var(--color-brand)_0%,rgba(200,16,46,0.16)_42%,transparent_100%)]';
    $arrow = '<svg width="14" height="12" viewBox="0 0 16 14" fill="none" aria-hidden="true" class="rtl:-scale-x-100"><path d="M7.99997 0.414307C7.99997 1.11051 8.68991 2.15231 9.38737 3.02724C10.2855 4.15371 11.3574 5.13777 12.5873 5.88911C13.5087 6.45171 14.6276 6.99177 15.5264 6.99177M7.99997 13.5855C7.99997 12.8893 8.68991 11.8475 9.38737 10.9726C10.2855 9.84617 11.3574 8.86204 12.5873 8.11071C13.5087 7.54811 14.6276 7.00804 15.5264 7.00804M15.5264 6.99991H0.473572" stroke="currentColor" /></svg>';
    $pin = '<path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z" /><circle cx="12" cy="10" r="3" />';
    $specIcons = [
        'type' => '<path d="M2 4v16" /><path d="M2 8h18a2 2 0 0 1 2 2v10" /><path d="M2 17h20" /><path d="M6 8v9" />',
        'beds' => '<path d="M2 4v16" /><path d="M2 8h18a2 2 0 0 1 2 2v10" /><path d="M2 17h20" /><path d="M6 8v9" />',
        'baths' => '<path d="M9 6 6.5 3.5a1.5 1.5 0 0 0-1-.5C4.683 3 4 3.683 4 4.5V17a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5" /><line x1="10" y1="5" x2="8" y2="7" /><line x1="2" y1="12" x2="22" y2="12" />',
        'area' => '<rect x="3" y="3" width="18" height="18" rx="2" /><path d="M3 9h18M9 21V9" />',
        'floors' => '<rect x="4" y="2" width="16" height="20" rx="2" /><path d="M9 22v-4h6v4" /><path d="M8 6h.01M16 6h.01M8 10h.01M16 10h.01M8 14h.01M16 14h.01" />',
    ];
    $stats = array_filter([
        $p['units'] ? [e(number_format($p['units'])), __('Total Units')] : null,
        $p['delivery'] ? [e($p['delivery'][0]).' <em>'.e($p['delivery'][1]).'</em>', __('Delivery')] : null,
        // As the design's "<em>4.2</em>M EGP": only the number in colour.
        $p['priceShort'] ? [preg_replace('/^([\d.,]+)/', '<em>$1</em>', e($p['priceShort'])).' '.e(__('EGP')), __('Starting Price')] : null,
        $p['sizes'] ? [e($p['sizes']).' <em>'.e(__('m²')).'</em>', __('Unit Sizes')] : null,
    ]);
    $statCols = [1 => 'grid-cols-1', 2 => 'grid-cols-2', 3 => 'grid-cols-3', 4 => 'grid-cols-4'][count($stats)] ?? 'grid-cols-4';
    $metrics = array_filter([
        $p['downPayment'] !== null ? [e($p['downPayment']).'<em>%</em>', __('Down Payment'), __('Minimum booking percentage with flexible payment plans available on request')] : null,
        $p['installmentYears'] ? [e($p['installmentYears']).' <em>'.e(trans_choice('Yr|Yrs', $p['installmentYears'])).'</em>', __('Installment Plan'), trans_choice('Extended payment schedule of up to :count year|Extended payment schedule of up to :count years', $p['installmentYears'], ['count' => $p['installmentYears']])] : null,
    ]);
    $priceLine = collect([$p['price'] ? __('From :price EGP', ['price' => $p['price']]) : null, $p['status']])->filter()->implode(' · ');
    $contact = 'flex items-center gap-[10px] rounded-[10px] border border-white/8 bg-white/4 px-4 py-[11px] font-sans text-[12.5px] font-medium text-white/75 transition-[border-color,background,color] duration-220 hover:border-white/22 hover:bg-white/8 hover:text-white [&_svg]:shrink-0 [&_svg]:opacity-55 hover:[&_svg]:opacity-85';
@endphp

<x-website.layout :title="$p['title']" :description="$p['short']">
    <div
        x-data="projectPage(@js(['count' => $count]))"
        @keydown.window="key($event)"
        data-pdf-phone="{{ $business->hotline }}"
        data-pdf-email="{{ $business->email }}"
        class="leading-[normal]"
    >
        {{-- Hero --}}
        <section class="relative flex min-h-screen flex-col overflow-hidden bg-[rgb(7,6,10)]" aria-label="{{ __('Project hero') }}">
            {{-- Phones take the card size; the photo is asked for early (it's the page's biggest thing). --}}
            @push('head')
                <link rel="preload" as="image" href="{{ $p['imageSmall'] }}" media="(max-width: 768px)" fetchpriority="high" />
                <link rel="preload" as="image" href="{{ $p['image'] }}" media="(min-width: 769px)" fetchpriority="high" />
            @endpush
            <div x-ref="heroBg" data-pdf-hero class="absolute inset-0 bg-[rgb(7,6,10)] bg-(image:--bg-small) bg-cover bg-center bg-no-repeat brightness-32 saturate-75 transition-transform duration-50 ease-linear will-change-transform min-[769px]:bg-(image:--bg)" style="--bg: url('{{ $p['image'] }}'); --bg-small: url('{{ $p['imageSmall'] }}')" aria-hidden="true"></div>
            <div class="pointer-events-none absolute inset-0 z-1 opacity-4 {{ $grain }}" aria-hidden="true"></div>
            <div class="absolute inset-0 z-2 bg-[linear-gradient(rgba(7,6,10,0.62)_0%,rgba(7,6,10,0.12)_38%,rgba(7,6,10,0.48)_68%,rgba(7,6,10,0.92)_100%)]" aria-hidden="true"></div>
            <div class="pointer-events-none absolute bottom-[60px] left-1/2 z-2 h-[360px] w-[800px] -translate-x-1/2 bg-[radial-gradient(rgba(200,16,46,0.09)_0%,transparent_68%)]" aria-hidden="true"></div>

            <nav class="absolute inset-x-0 top-[calc(var(--spacing-nav)+22px)] z-10 flex items-center gap-[7px] px-[clamp(24px,5vw,80px)] font-sans text-[11px] font-medium tracking-[0.07em] text-white/42 rtl:font-arabic rtl:tracking-normal" aria-label="{{ __('Breadcrumb') }}">
                <a href="{{ route('home') }}" class="text-white/42 transition-colors duration-200 hover:text-white/72">{{ __('Home') }}</a>
                <span class="opacity-35" aria-hidden="true"><span class="rtl:hidden">›</span><span class="ltr:hidden">‹</span></span>
                <a href="{{ route('projects') }}" class="text-white/42 transition-colors duration-200 hover:text-white/72">{{ __('Projects') }}</a>
                <span class="opacity-35" aria-hidden="true"><span class="rtl:hidden">›</span><span class="ltr:hidden">‹</span></span>
                <span class="text-white/68">{{ $p['title'] }}</span>
            </nav>

            <div class="relative z-10 flex flex-1 flex-col items-center justify-center px-[clamp(24px,7vw,96px)] pt-[calc(var(--spacing-nav)+90px)] pb-[90px] text-center">
                @if ($p['status'] || $p['usage'])
                    <div class="mb-6 flex items-center justify-center gap-[10px] max-[480px]:flex-wrap">
                        @if ($p['status'])
                            <span data-pdf-status class="inline-flex items-center gap-[7px] rounded-full border border-[rgba(200,16,46,0.42)] bg-[rgba(200,16,46,0.1)] px-[15px] py-1.5 font-sans text-[9.5px] font-bold tracking-[0.22em] text-white/82 uppercase backdrop-blur-[12px] rtl:font-arabic rtl:tracking-normal">
                                <span class="size-[5px] shrink-0 animate-status-pulse rounded-full bg-brand" aria-hidden="true"></span>
                                {{ $p['status'] }}
                            </span>
                        @endif
                        @if ($p['usage'])
                            <span data-pdf-type class="inline-flex items-center gap-1.5 rounded-full border border-white/12 bg-white/6 px-[14px] py-1.5 font-sans text-[9.5px] font-semibold tracking-[0.18em] text-white/55 uppercase backdrop-blur-[12px] rtl:font-arabic rtl:tracking-normal">{{ $p['usage'] }}</span>
                        @endif
                    </div>
                @endif

                <h1 data-pdf-name class="mb-6 font-sans text-[clamp(42px,6.5vw,90px)] leading-[1.04] font-light tracking-[-0.015em] text-white rtl:font-arabic rtl:leading-[1.3]">{{ $p['title'] }}</h1>

                @if ($p['developer'] || $p['place'])
                    <div class="mb-[18px] flex flex-wrap items-center justify-center gap-x-[18px] gap-y-1.5 max-[480px]:gap-2">
                        @if ($p['developer'])
                            <span data-pdf-dev class="inline-flex items-center gap-[9px] font-sans text-[11px] font-semibold tracking-[0.1em] text-white/50 uppercase rtl:font-arabic rtl:tracking-normal">
                                <span data-pdf-dev-logo class="flex size-[26px] shrink-0 items-center justify-center rounded-full border border-white/16 bg-white/7 font-sans text-[8px] font-bold text-white/65" aria-hidden="true">{{ $p['initials'] }}</span>
                                {{ __('By :developer', ['developer' => $p['developer']]) }}
                            </span>
                        @endif
                        @if ($p['developer'] && $p['place'])
                            <span class="size-[3px] shrink-0 rounded-full bg-white/18" aria-hidden="true"></span>
                        @endif
                        @if ($p['place'])
                            <span data-pdf-location class="inline-flex items-center gap-1.5 font-sans text-[11px] tracking-[0.04em] text-white/40 rtl:font-arabic rtl:tracking-normal">
                                <svg width="11" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 opacity-55" aria-hidden="true">{!! $pin !!}</svg>
                                {{ $p['place'] }}
                            </span>
                        @endif
                    </div>
                @endif

                @if ($p['short'])
                    <p class="max-w-[480px] font-sans text-[clamp(13px,1.2vw,15px)] leading-[1.8] font-light text-white/38 rtl:font-arabic">{{ $p['short'] }}</p>
                @endif

                <button
                    type="button"
                    @click="open(0)"
                    class="absolute right-[clamp(20px,4vw,56px)] bottom-[22px] inline-flex cursor-pointer items-center gap-[10px] rounded-full border-[1.5px] border-brand bg-brand py-[11px] pr-5 pl-[14px] font-sans text-[11px] leading-[normal] font-bold tracking-[0.12em] text-white uppercase backdrop-blur-[20px] transition-[border-color,background,color,translate] duration-250 hover:-translate-y-0.5 hover:border-[rgb(168,13,38)] hover:bg-[rgb(168,13,38)] rtl:right-auto rtl:left-[clamp(20px,4vw,56px)] rtl:pr-[14px] rtl:pl-5 rtl:font-arabic rtl:tracking-normal max-md:right-4 max-md:rtl:left-4 max-[480px]:static max-[480px]:mt-7 max-[480px]:self-end"
                    aria-label="{{ trans_choice('View gallery (:count photo)|View gallery (:count photos)', $count, ['count' => $count]) }}"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" /><circle cx="8.5" cy="8.5" r="1.5" /><polyline points="21,15 16,10 5,21" /></svg>
                    {{ __('View Gallery') }}
                    <span class="inline-flex size-5 shrink-0 items-center justify-center rounded-full bg-white/25 text-[9px] font-bold text-white" aria-hidden="true">{{ $count }}</span>
                </button>
            </div>

            @if ($stats)
                <div class="relative z-10 grid {{ $statCols }} border-t border-white/7 bg-[rgba(7,6,10,0.8)] backdrop-blur-[36px] backdrop-saturate-140 max-md:grid-cols-2" role="list" aria-label="{{ __('Project key figures') }}">
                    @foreach ($stats as [$value, $label])
                        <div data-pdf-stat class="relative flex flex-col items-center justify-center gap-1.5 px-3 py-[22px] not-first:before:absolute not-first:before:top-[24%] not-first:before:bottom-[24%] not-first:before:left-0 not-first:before:w-px not-first:before:bg-white/7 rtl:not-first:before:right-0 rtl:not-first:before:left-auto max-md:nth-[n+3]:border-t max-md:nth-[n+3]:border-white/7 max-md:nth-3:before:hidden" role="listitem">
                            <span data-pdf-val class="font-sans text-[clamp(19px,2.2vw,28px)] leading-none font-light tracking-[-0.01em] text-white [&_em]:text-brand [&_em]:not-italic">{!! $value !!}</span>
                            <span data-pdf-label class="font-sans text-[9px] font-bold tracking-[0.18em] text-white/36 uppercase rtl:font-arabic rtl:tracking-normal">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Overview --}}
        <section id="overview" class="relative overflow-hidden bg-page pt-[clamp(88px,11vw,144px)] pb-[clamp(88px,11vw,132px)] {{ $line }} after:pointer-events-none after:absolute after:inset-0 after:z-0 after:opacity-[0.022] after:bg-(image:--grain) after:bg-size-[200px_auto] dark:bg-[rgb(13,11,8)] max-[560px]:before:right-5 max-[560px]:before:left-5" aria-label="{{ __('Project overview') }}">
            <div @class([
                'relative z-2 mx-auto grid max-w-[1320px] items-start gap-[clamp(48px,7vw,100px)] px-12 max-[960px]:grid-cols-1 max-[960px]:gap-14 max-[560px]:px-5',
                'grid-cols-2' => $metrics,
            ])>
                <div class="flex flex-col">
                    <p {!! $intersect !!} class="{{ $reveal }} {{ $eyebrow }} mb-5">{{ __('Project Overview') }}</p>
                    <h2 {!! $intersect !!} class="{{ $reveal }} delay-120 {{ $title }} mb-6 text-[clamp(34px,4vw,54px)] text-ink">{!! __('About <em>:project</em>', ['project' => e($p['title'])]) !!}</h2>
                    @if ($p['description'])
                        <div data-pdf-desc {!! $intersect !!} class="{{ $reveal }} delay-240 [&_p]:mb-[18px] [&_p]:font-sans [&_p]:text-[15px] [&_p]:leading-[1.9] [&_p]:font-light [&_p]:text-ink-60 rtl:[&_p]:font-arabic">{!! $p['description'] !!}</div>
                    @endif
                    <div {!! $intersect !!} class="{{ $reveal }} delay-360 mt-[10px] flex flex-wrap items-center gap-4 max-[560px]:flex-col max-[560px]:items-stretch">
                        <a href="#contact" class="inline-flex items-center gap-[9px] rounded-full border border-white/18 bg-[linear-gradient(135deg,rgba(215,20,52,0.96)_0%,rgb(185,10,36)_100%)] px-[26px] py-[13px] font-sans text-[11.5px] font-bold tracking-[0.1em] text-white uppercase transition-transform duration-250 ease-out-ex hover:-translate-y-0.5 rtl:font-arabic rtl:tracking-normal max-[560px]:justify-center">
                            {{ __('Enquire Now') }} {!! $arrow !!}
                        </a>
                        <button
                            type="button"
                            @click="pdf()"
                            :disabled="pdfLoading"
                            class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-[rgba(13,13,13,0.18)] bg-transparent px-[26px] py-[15px] font-sans text-[11.5px] leading-none font-bold tracking-[0.1em] whitespace-nowrap text-ink uppercase transition-[border-color,color,translate] duration-250 ease-out-ex hover:-translate-y-0.5 hover:border-brand hover:text-brand dark:border-[rgba(237,232,223,0.18)] dark:text-[rgba(237,232,223,0.75)] dark:hover:border-brand dark:hover:text-brand rtl:font-arabic rtl:tracking-normal max-[560px]:justify-center"
                            aria-label="{{ __('Download project PDF') }}"
                        >
                            <span x-show="pdfLoading" x-cloak class="size-[13px] animate-spin rounded-full border-2 border-[rgba(13,13,13,0.15)] border-t-brand [animation-duration:0.7s] dark:border-[rgba(237,232,223,0.15)] dark:border-t-brand" aria-hidden="true"></span>
                            <svg x-show="! pdfLoading" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><line x1="12" y1="18" x2="12" y2="12" /><line x1="9" y1="15" x2="15" y2="15" /></svg>
                            {{ __('Download PDF') }}
                        </button>
                    </div>
                </div>

                @if ($metrics)
                    <div {!! $intersect !!} class="{{ $reveal }} grid grid-cols-2 gap-[14px]">
                        @foreach ($metrics as [$value, $label, $desc])
                            <div data-pdf-metric class="group relative overflow-hidden rounded-2xl border border-ink-10 bg-page px-[22px] py-[26px] transition-[border-color,box-shadow,background] duration-300 before:absolute before:inset-x-0 before:top-0 before:h-0.5 before:bg-[linear-gradient(to_right,var(--color-brand),transparent)] before:opacity-0 before:transition-opacity before:duration-300 dark:border-white/8 dark:bg-white/3 dark:hover:border-[rgba(200,16,46,0.25)] dark:hover:bg-white/5">
                                <span data-pdf-val class="mb-2 block font-sans text-[clamp(28px,3.2vw,44px)] leading-none font-light tracking-[-0.02em] text-ink [&_em]:text-brand [&_em]:not-italic">{!! $value !!}</span>
                                <span data-pdf-label class="mb-[7px] block font-sans text-[9.5px] font-bold tracking-[0.2em] text-ink-60 uppercase rtl:font-arabic rtl:tracking-normal">{{ $label }}</span>
                                <span class="block font-sans text-[12px] leading-[1.55] font-light text-ink-30 rtl:font-arabic">{{ $desc }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- Unit types --}}
        @if ($p['types'])
            <section id="units" class="relative overflow-hidden bg-[rgb(13,11,8)] pt-[clamp(88px,11vw,144px)] pb-[clamp(72px,10vw,120px)]" aria-label="{{ __('Unit types') }}">
                <div class="pointer-events-none absolute top-0 left-1/2 h-[420px] w-[900px] -translate-x-1/2 bg-[radial-gradient(rgba(200,16,46,0.06)_0%,transparent_68%)]" aria-hidden="true"></div>

                <div {!! $intersect !!} class="{{ $reveal }} mx-auto mb-[clamp(28px,4.5vw,52px)] flex max-w-[1320px] flex-wrap items-end justify-between gap-5 px-12 max-[900px]:px-6 max-md:px-5">
                    <div class="flex flex-col">
                        <p class="{{ $eyebrow }} mb-[18px]">{{ __('Unit Types') }}</p>
                        <h2 class="{{ $title }} text-[clamp(30px,3.8vw,50px)] text-white">{!! __('Select Your <em>Residence</em>') !!}</h2>
                    </div>
                    <span class="font-sans text-[12px] tracking-[0.04em] text-white/35 rtl:font-arabic rtl:tracking-normal">{{ trans_choice(':count configuration|:count configurations', count($p['types']), ['count' => count($p['types'])]) }} · {{ trans_choice(':count total unit|:count total units', $p['units'], ['count' => number_format($p['units'])]) }}</span>
                </div>

                <div class="mx-auto mb-9 flex max-w-[1320px] flex-wrap gap-2 px-12 max-[900px]:px-6 max-md:px-5" role="tablist" aria-label="{{ __('Select unit type') }}">
                    @foreach ($p['types'] as $i => $type)
                        <button
                            type="button"
                            role="tab"
                            id="unit-tab-{{ $type['id'] }}"
                            aria-controls="unit-{{ $type['id'] }}"
                            @click="tab = {{ $i }}"
                            :aria-selected="tab === {{ $i }}"
                            class="cursor-pointer rounded-full border px-5 py-[9px] font-sans text-[11px] leading-[normal] font-bold tracking-[0.12em] uppercase transition-[border-color,background,color] duration-220 rtl:font-arabic rtl:tracking-normal"
                            :class="tab === {{ $i }} ? 'border-[rgba(200,16,46,0.5)] bg-[rgba(200,16,46,0.12)] text-white' : 'border-white/10 bg-white/4 text-white/45 hover:border-white/22 hover:bg-white/7 hover:text-white/78'"
                        >{{ $type['name'] }}</button>
                    @endforeach
                </div>

                <div class="mx-auto max-w-[1320px] px-12 max-[900px]:px-6 max-md:px-5">
                    @foreach ($p['types'] as $i => $type)
                        @php
                            $specs = array_filter([
                                'type' => [$type['name'], __('Type')],
                                'beds' => $type['beds'] !== null ? [$type['beds'], __('Bedrooms')] : null,
                                'baths' => $type['baths'] !== null ? [$type['baths'], __('Bathrooms')] : null,
                                'area' => $type['area'] !== null ? [$type['area'], __('sqm')] : null,
                                'floors' => $type['floors'] !== null ? [$type['floors'], __('Floors')] : null,
                            ]);
                        @endphp
                        <div
                            id="unit-{{ $type['id'] }}"
                            role="tabpanel"
                            aria-labelledby="unit-tab-{{ $type['id'] }}"
                            x-show="tab === {{ $i }}"
                            @if ($i > 0) x-cloak style="display: none" @endif
                            class="grid animate-panel-in grid-cols-2 items-start gap-[clamp(36px,6vw,88px)] max-[900px]:grid-cols-1 max-[900px]:gap-9"
                        >
                            <div class="group relative aspect-[4/3] overflow-hidden rounded-[20px] after:pointer-events-none after:absolute after:inset-0 after:z-1 after:bg-[linear-gradient(transparent_45%,rgba(0,0,0,0.42)_100%)] max-[560px]:aspect-[3/2]">
                                <img src="{{ $type['image'] }}" alt="{{ $type['name'] }}" loading="lazy" class="block size-full object-cover transition-transform duration-1000 ease-out-ex group-hover:scale-104" />
                                <span class="absolute bottom-4 left-4 z-2 inline-flex items-center rounded-full border border-white/10 bg-[rgba(7,6,10,0.74)] px-[13px] py-1.5 font-sans text-[9.5px] font-bold tracking-[0.16em] text-white/82 uppercase backdrop-blur-[18px] rtl:right-4 rtl:left-auto rtl:font-arabic rtl:tracking-normal">{{ $type['name'] }}@if ($type['area']) · {{ $type['area'] }} {{ __('m²') }}@endif</span>
                            </div>
                            <div class="flex flex-col pt-1.5">
                                <span class="mb-[10px] font-sans text-[clamp(26px,2.8vw,38px)] leading-none font-light tracking-[-0.01em] text-white rtl:font-arabic rtl:leading-[1.3]">{{ $type['name'] }}</span>
                                @if ($type['area'] || $type['floors'])
                                    <span class="mb-[14px] font-sans text-[12.5px] tracking-[0.04em] text-white/42 rtl:font-arabic rtl:tracking-normal">{{ collect([$type['area'] ? $type['area'].' '.__('m²') : null, $type['floors'] ? __('Floors :floors', ['floors' => $type['floors']]) : null])->filter()->implode(' · ') }}</span>
                                @endif
                                @if ($type['price'])
                                    <span class="mb-7 flex items-baseline gap-1.5 font-sans text-[12px] font-light text-white/50 rtl:font-arabic">{{ __('Starting from') }} <span class="font-sans text-[clamp(20px,2.2vw,28px)] font-light tracking-[-0.01em] text-white">{{ $type['price'] }} {{ __('EGP') }}</span></span>
                                @endif
                                <div class="mb-6 flex flex-wrap gap-6 border-b border-white/7 pb-6">
                                    @foreach ($specs as $key => [$value, $label])
                                        <div class="flex flex-col gap-[5px]">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="text-white/30" aria-hidden="true">{!! $specIcons[$key] !!}</svg>
                                            <span class="font-sans text-[15px] leading-none font-light text-white rtl:font-arabic">{{ $value }}</span>
                                            <span class="font-sans text-[9px] font-bold tracking-[0.16em] text-white/32 uppercase rtl:font-arabic rtl:tracking-normal">{{ $label }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                @if ($type['features'])
                                    <div class="mb-[26px] flex flex-wrap gap-[7px]">
                                        @foreach ($type['features'] as $feature)
                                            <span class="rounded-full border border-white/9 bg-white/4 px-3 py-[5px] font-sans text-[11px] font-medium tracking-[0.03em] text-white/55 rtl:font-arabic rtl:tracking-normal">{{ $feature }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                <span class="mb-7 inline-flex items-center gap-2 font-sans text-[12px] font-medium text-white/45 rtl:font-arabic">
                                    <span class="size-[7px] shrink-0 animate-avail-glow rounded-full bg-[rgb(46,204,113)]" aria-hidden="true"></span>
                                    {{ trans_choice(':count unit currently available|:count units currently available', $type['units'], ['count' => number_format($type['units'])]) }}
                                </span>
                                <a
                                    href="#contact"
                                    @click="$dispatch('project-unit-type', {{ $type['id'] }})"
                                    class="inline-flex items-center gap-[9px] self-start rounded-full border border-[rgba(200,16,46,0.42)] bg-[rgba(200,16,46,0.1)] px-[26px] py-[13px] font-sans text-[11.5px] font-bold tracking-[0.1em] text-white uppercase transition-[background,border-color,translate] duration-250 hover:-translate-y-0.5 hover:border-[rgba(200,16,46,0.68)] hover:bg-[rgba(200,16,46,0.22)] rtl:font-arabic rtl:tracking-normal"
                                >{{ __('Enquire About This Unit') }} {!! $arrow !!}</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Amenities + location --}}
        @if ($p['amenities'] || $p['address'] || $p['nearby'])
            <section id="amenities" class="relative overflow-hidden bg-[rgb(244,242,238)] pt-[clamp(88px,11vw,144px)] pb-[clamp(72px,9vw,112px)] {{ $line }} after:pointer-events-none after:absolute after:inset-0 after:z-0 after:opacity-[0.018] after:bg-(image:--grain) after:bg-size-[200px_auto] dark:bg-[rgb(16,14,11)] max-[600px]:before:right-5 max-[600px]:before:left-5" aria-label="{{ __('Amenities and location') }}">
                <div class="relative z-2 mx-auto max-w-[1320px] px-12 max-[600px]:px-5">
                    @if ($p['amenities'])
                        <div {!! $intersect !!} class="{{ $reveal }} mb-[clamp(36px,5vw,60px)] flex flex-wrap items-end justify-between gap-4">
                            <div class="flex flex-col">
                                <p class="{{ $eyebrow }} mb-[18px]">{{ __('Amenities') }}</p>
                                <h2 class="{{ $title }} text-[clamp(30px,3.8vw,50px)] text-ink">{!! __('Designed for the Life <em>You Deserve</em>') !!}</h2>
                            </div>
                            <span class="inline-flex items-center gap-[7px] rounded-full border border-[rgba(200,16,46,0.22)] bg-[rgba(200,16,46,0.06)] px-4 py-2 font-sans text-[11px] font-bold tracking-[0.12em] whitespace-nowrap text-brand uppercase rtl:font-arabic rtl:tracking-normal">{{ trans_choice(':count Amenity|:count Amenities', count($p['amenities']), ['count' => count($p['amenities'])]) }}</span>
                        </div>
                        <div {!! $intersect !!} class="{{ $reveal }} mb-[clamp(64px,9vw,100px)] grid grid-cols-3 gap-[10px] max-[960px]:grid-cols-2 max-[600px]:grid-cols-1">
                            @foreach ($p['amenities'] as $amenity)
                                <div class="flex items-center gap-[10px] rounded-lg border border-ink-10 bg-paper p-3 transition-[border-color] duration-200 hover:border-brand-lt dark:border-white/8 dark:bg-white/4">
                                    <div class="flex size-9 shrink-0 items-center justify-center rounded-md bg-white/12 text-brand backdrop-blur-[8px]" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-[17px]"><polyline points="20 6 9 17 4 12" /></svg>
                                    </div>
                                    <span data-pdf-amenity class="font-sans text-[12px] leading-[1.3] font-medium text-ink rtl:font-arabic">{{ $amenity }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($p['address'] || $p['nearby'])
                        <div class="grid grid-cols-[1fr_1.15fr] items-start gap-[clamp(40px,6vw,88px)] max-[960px]:grid-cols-1 max-[960px]:gap-12">
                            <div {!! $intersect !!} class="{{ $reveal }} flex flex-col">
                                <p class="{{ $eyebrow }} mb-[18px]">{{ __('Location') }}</p>
                                <h2 class="{{ $title }} mb-[14px] text-[clamp(26px,3vw,40px)] text-ink">
                                    @if ($p['city'])
                                        {!! __('At the Heart of <em>:place</em>', ['place' => e($p['city'])]) !!}
                                    @else
                                        {{ __('Location') }}
                                    @endif
                                </h2>
                                @if ($p['address'])
                                    <p class="mb-8 flex items-start gap-[9px] font-sans text-[14px] leading-[1.65] font-light text-ink-60 rtl:font-arabic">
                                        <svg width="14" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0 text-brand" aria-hidden="true">{!! $pin !!}</svg>
                                        {{ $p['address'] }}
                                    </p>
                                @endif
                                @if ($p['nearby'])
                                    <div class="flex flex-col" role="list" aria-label="{{ __('Nearby landmarks') }}">
                                        @foreach ($p['nearby'] as $place)
                                            <div class="flex items-center gap-3 border-b border-ink-10 py-[13px] first:border-t" role="listitem">
                                                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-[rgba(200,16,46,0.07)] text-brand" aria-hidden="true">
                                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $pin !!}</svg>
                                                </span>
                                                <span class="flex-1 font-sans text-[13.5px] font-medium text-ink rtl:font-arabic">{{ $place }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div {!! $intersect !!} class="{{ $reveal }} delay-120 group relative overflow-hidden rounded-[20px]">
                                <img src="{{ $p['images'][1] ?? $p['image'] }}" alt="{{ $p['place'] ?: $p['title'] }}" loading="lazy" class="block aspect-[4/3] w-full object-cover brightness-88 saturate-78 transition-[transform,filter] duration-900 ease-out-ex group-hover:scale-103 group-hover:brightness-92 group-hover:saturate-88" />
                                <div class="pointer-events-none absolute inset-0 z-1 bg-[linear-gradient(transparent_40%,rgba(7,6,10,0.72)_100%)]" aria-hidden="true"></div>
                                <div class="absolute inset-x-5 bottom-5 z-2 flex flex-wrap items-center justify-between gap-[10px]">
                                    @if ($p['place'])
                                        <span class="flex items-center gap-[7px] font-sans text-[12px] font-semibold tracking-[0.05em] text-white/90 rtl:font-arabic rtl:tracking-normal">
                                            <svg width="12" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 text-brand" aria-hidden="true">{!! $pin !!}</svg>
                                            {{ $p['place'] }}
                                        </span>
                                    @endif
                                    <a href="{{ $maps }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-[rgba(7,6,10,0.58)] px-[14px] py-[7px] font-sans text-[9.5px] font-bold tracking-[0.14em] text-white/82 uppercase backdrop-blur-[16px] transition-[border-color,color,background] duration-220 hover:border-white/42 hover:bg-[rgba(7,6,10,0.78)] hover:text-white rtl:font-arabic rtl:tracking-normal" aria-label="{{ __('Open in Google Maps') }}">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" /><polyline points="15 3 21 3 21 9" /><line x1="10" y1="14" x2="21" y2="3" /></svg>
                                        {{ __('Open in Maps') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        {{-- Contact --}}
        <section id="contact" class="relative overflow-hidden bg-[rgb(7,6,10)] pt-[clamp(88px,11vw,144px)] pb-[clamp(80px,10vw,128px)]" aria-label="{{ __('Contact the sales team') }}">
            {{-- Barely visible (7%): the card size is enough, fetched when the visitor nears it. --}}
            <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-7 grayscale" data-bg="{{ $p['imageSmall'] }}" x-intersect.once.margin.600px="$el.style.backgroundImage = `url('${$el.dataset.bg}')`" aria-hidden="true"></div>
            <div class="pointer-events-none absolute inset-0 z-1 opacity-4 {{ $grain }}" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-[60px] left-1/2 z-1 h-[400px] w-[800px] -translate-x-1/2 bg-[radial-gradient(rgba(200,16,46,0.1)_0%,transparent_70%)]" aria-hidden="true"></div>

            <div class="relative z-2 mx-auto max-w-[1320px] px-12 max-[600px]:px-5">
                <div {!! $intersect !!} class="{{ $reveal }} mb-[clamp(40px,6vw,68px)] max-w-[580px]">
                    <p class="{{ $eyebrow }} mb-[18px]">{{ __('Contact') }}</p>
                    <h2 class="{{ $title }} mb-4 text-[clamp(32px,4vw,54px)] text-white">{!! __('Reserve Your Unit <em>Today</em>') !!}</h2>
                    <p class="font-sans text-[15px] leading-[1.75] font-light text-white/45 rtl:font-arabic">{{ __('Our dedicated sales advisors are on hand to guide you through every step — from choosing your ideal unit to finalising your payment plan.') }}</p>
                </div>

                <div class="grid grid-cols-[1fr_1.45fr] items-start gap-[clamp(32px,5vw,72px)] max-[960px]:grid-cols-1">
                    <div {!! $intersect !!} class="{{ $reveal }} flex flex-col gap-5 max-[960px]:flex-row max-[960px]:flex-wrap max-[600px]:flex-col">
                        <div class="flex items-center gap-4 rounded-2xl border border-white/8 bg-white/4 p-[22px] backdrop-blur-[16px] max-[960px]:min-w-[260px] max-[960px]:flex-1">
                            <img src="{{ $p['thumb'] }}" alt="{{ $p['title'] }}" loading="lazy" class="size-[60px] shrink-0 rounded-[10px] border border-white/10 object-cover" />
                            <div class="flex flex-col gap-1">
                                <span class="font-sans text-[15px] leading-[1.2] font-semibold text-white rtl:font-arabic">{{ $p['title'] }}</span>
                                @if ($p['place'])
                                    <span class="flex items-center gap-[5px] font-sans text-[11.5px] text-white/45 rtl:font-arabic">
                                        <svg width="10" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-60" aria-hidden="true">{!! $pin !!}</svg>
                                        {{ $p['place'] }}
                                    </span>
                                @endif
                                @if ($priceLine)
                                    <span class="mt-0.5 font-sans text-[11px] font-semibold tracking-[0.04em] text-brand rtl:font-arabic rtl:tracking-normal">{{ $priceLine }}</span>
                                @endif
                            </div>
                        </div>

                        @if ($phone || $email || $whatsapp)
                            <div class="rounded-2xl border border-white/8 bg-white/4 p-6 backdrop-blur-[16px] max-[960px]:min-w-[260px] max-[960px]:flex-1">
                                <span class="mb-4 block font-sans text-[9px] font-bold tracking-[0.22em] text-white/30 uppercase rtl:font-arabic rtl:tracking-normal">{{ $agent ? __('Your Sales Advisor') : __('Talk to Our Sales Team') }}</span>
                                @if ($agent)
                                    <div class="mb-5 flex items-center gap-[14px]">
                                        @if ($agent['photo'])
                                            <img src="{{ $agent['photo'] }}" alt="{{ $agent['name'] }}" loading="lazy" class="size-[46px] shrink-0 rounded-full border border-[rgba(200,16,46,0.3)] object-cover" />
                                        @else
                                            <span class="flex size-[46px] shrink-0 items-center justify-center rounded-full border border-[rgba(200,16,46,0.3)] bg-[rgba(200,16,46,0.18)] font-sans text-[14px] font-bold text-brand" aria-hidden="true">{{ $agent['initials'] }}</span>
                                        @endif
                                        <div>
                                            <div class="font-sans text-[15px] leading-[1.2] font-semibold text-white rtl:font-arabic">{{ $agent['name'] }}</div>
                                            @if ($agent['role'])
                                                <div class="mt-0.5 font-sans text-[11px] text-white/40 rtl:font-arabic">{{ $agent['role'] }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                <div class="flex flex-col gap-[10px]">
                                    @if ($phone)
                                        <a href="tel:{{ $phone }}" @click="$track('phone', { project_id: {{ $p['id'] }} })" class="{{ $contact }}">
                                            <x-website.icons.phone width="14" height="14" />
                                            <span dir="ltr">{{ $phone }}</span>
                                        </a>
                                    @endif
                                    @if ($email)
                                        <a href="mailto:{{ $email }}" class="{{ $contact }}">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" /><polyline points="22,6 12,13 2,6" /></svg>
                                            {{ $email }}
                                        </a>
                                    @endif
                                    @if ($whatsapp)
                                        <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" @click="$track('whatsapp', { project_id: {{ $p['id'] }} })" class="{{ $contact }} border-[rgba(37,211,102,0.2)]! bg-[rgba(37,211,102,0.06)]! hover:border-[rgba(37,211,102,0.5)]! hover:bg-[rgba(37,211,102,0.12)]! [&_svg]:text-[rgb(37,211,102)] [&_svg]:opacity-100!">
                                            <x-website.icons.whatsapp width="14" height="14" />
                                            {{ __('Chat on WhatsApp') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    <div {!! $intersect !!} class="{{ $reveal }} delay-120 rounded-[20px] border border-white/8 bg-white/4 p-[clamp(24px,4vw,40px)] backdrop-blur-[16px]">
                        <p class="mb-6 font-sans text-[16px] font-semibold text-white rtl:font-arabic">{{ __('Send an Enquiry') }}</p>
                        <livewire:website.project-enquiry :project-id="$p['id']" :types="$p['types']" />
                    </div>
                </div>
            </div>
        </section>

        {{-- Gallery lightbox (the design's .glb) --}}
        <div
            x-show="gallery"
            x-cloak
            x-transition.opacity.duration.300ms
            @click.self="close()"
            class="fixed inset-0 z-9000 flex items-center justify-center bg-[rgba(7,6,10,0.96)]"
            style="display: none"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('Project gallery') }}"
        >
            @php($glbButton = 'z-10 flex cursor-pointer items-center justify-center rounded-full border border-white/18 bg-white/6 text-white/85 transition-[background,border-color] duration-200 hover:border-white/38 hover:bg-white/14')
            <button type="button" x-ref="galleryClose" @click="close()" class="{{ $glbButton }} absolute top-5 right-6 size-10 rtl:right-auto rtl:left-6" aria-label="{{ __('Close gallery') }}">
                <x-website.icons.close width="16" height="16" stroke-width="2" />
            </button>
            <span class="absolute top-6 left-1/2 z-10 -translate-x-1/2 font-sans text-[11px] font-semibold tracking-[0.1em] text-white/50 uppercase" x-text="(index + 1) + ' / ' + {{ $count }}"></span>

            <div class="relative flex size-full items-center justify-center px-20 py-[72px] max-[600px]:px-12 max-[600px]:pt-16 max-[600px]:pb-[100px]" @click.self="close()">
                <button type="button" @click="go(index - 1)" class="{{ $glbButton }} absolute top-1/2 left-5 size-11 -translate-y-1/2 rtl:right-5 rtl:left-auto max-[600px]:left-2 max-[600px]:rtl:right-2" aria-label="{{ __('Previous') }}">
                    <x-website.icons.arrow class="size-4 -scale-x-100 stroke-2 rtl:scale-x-100" />
                </button>
                <img
                    :src="@js($p['images'])[index]"
                    @load="loaded = true"
                    alt="{{ $p['title'] }}"
                    draggable="false"
                    class="max-h-full max-w-full rounded-lg object-contain transition-opacity duration-250 select-none"
                    :class="loaded ? 'opacity-100' : 'opacity-0'"
                />
                <button type="button" @click="go(index + 1)" class="{{ $glbButton }} absolute top-1/2 right-5 size-11 -translate-y-1/2 rtl:right-auto rtl:left-5 max-[600px]:right-2 max-[600px]:rtl:left-2" aria-label="{{ __('Next') }}">
                    <x-website.icons.arrow class="size-4 stroke-2 rtl:-scale-x-100" />
                </button>
            </div>

            <div x-ref="thumbs" class="absolute bottom-[52px] left-1/2 z-10 flex max-w-[calc(100vw-120px)] -translate-x-1/2 gap-1.5 overflow-x-auto p-0.5 [scrollbar-width:none] max-[600px]:bottom-14 [&::-webkit-scrollbar]:hidden">
                @foreach ($p['thumbs'] as $i => $thumb)
                    <button
                        type="button"
                        @click="go({{ $i }})"
                        class="h-9 w-12 shrink-0 cursor-pointer overflow-hidden rounded border-[1.5px] transition-[border-color,opacity] duration-200"
                        :class="index === {{ $i }} ? 'border-brand opacity-100' : 'border-transparent opacity-50'"
                        aria-label="{{ __('Photo :number', ['number' => $i + 1]) }}"
                    ><img src="{{ $thumb }}" alt="" loading="lazy" class="block size-full object-cover" /></button>
                @endforeach
            </div>
        </div>
    </div>
</x-website.layout>
