{{--
    Why Choose Us (the design's .why): header, its button (not in the design; Dastan's, from Home settings: text and link,
    else the about page) and the four cards from Home settings, the four numbers from About Us
    settings (counted up when they scroll into view), and the cards on a dotted path drawn left to right.
    Behaviour: resources/js/website/why.js.
--}}
@php
    $home = app(\Modules\GeneralSettings\Settings\HomeSettings::class);
    $about = app(\Modules\GeneralSettings\Settings\AboutUsSettings::class);
    $locale = app()->getLocale();

    // The design colours the last two words of the title.
    $words = preg_split('/\s+/u', trim($home->{"why_choose_header_{$locale}"}));
    $title = e(implode(' ', array_slice($words, 0, -2))).' <em>'.e(implode(' ', array_slice($words, -2))).'</em>';

    $stats = [
        [$about->stats_properties_sold, '+', __('Luxury Properties')],
        [$about->years_of_excellence, '+', __('Years of Experience')],
        [$about->stats_commitment, '%', __('Client Satisfaction')],
        [$about->stats_happy_clients, '+', __('Happy Clients')],
    ];
    $illustrations = ['villa', 'tower', 'townhouse', 'estate'];
    // Only a page of the site ("/projects") or a web link; anything else (e.g. "javascript:") falls back to the about page.
    $ctaUrl = (string) $home->why_choose_cta_url;
    $ctaUrl = preg_match('#^(https?://|/(?!/))#i', $ctaUrl) ? $ctaUrl : route('about');
    $ctaText = $home->{"why_choose_cta_text_{$locale}"};
    $reveal = 'opacity-0 [transform:translateY(24px)]';
@endphp

<section id="why-us" x-data="whyChoose" class="relative overflow-hidden bg-page bg-cover bg-top bg-no-repeat pt-20 pb-[100px] before:pointer-events-none before:absolute before:inset-0 before:bg-[rgba(250,248,245,0.82)] dark:bg-[rgb(12,10,7)] dark:bg-none! dark:before:bg-transparent dark:before:bg-[radial-gradient(80%_60%_at_50%_40%,rgba(212,20,44,0.06)_0%,transparent_70%)]" data-bg="{{ \App\Support\Website\SiteImages::get('why_background')[0] }}" x-intersect.once.margin.600px="$el.style.backgroundImage = `url('${$el.dataset.bg}')`">
    <div class="relative mx-auto max-w-[1340px] px-[60px] max-[900px]:px-6 max-md:px-5">
        <header class="mb-16 text-center">
            <p data-why-reveal class="{{ $reveal }} mb-[22px] inline-flex items-center rounded-[20px] border border-white/65 bg-white/52 px-[18px] py-1.5 font-sans text-[9.5px] font-bold tracking-[0.3em] text-brand-dk uppercase shadow-[0_2px_16px_rgba(0,0,0,0.08),0_1px_4px_rgba(0,0,0,0.05)] backdrop-blur-[20px] backdrop-saturate-180 dark:border-[rgba(237,232,223,0.12)] dark:bg-[rgba(14,12,9,0.82)] dark:shadow-[0_2px_24px_rgba(0,0,0,0.5),inset_0_1px_0_rgba(237,232,223,0.06)] rtl:font-arabic rtl:tracking-[0.08em]">{{ $home->{"why_choose_subtitle_{$locale}"} }}</p>
            <h2 data-why-reveal class="{{ $reveal }} m-0 mb-[14px] font-sans text-[clamp(34px,3.8vw,54px)] leading-[1.1] font-light tracking-[-0.02em] text-ink rtl:font-arabic max-[480px]:text-[32px] [&_em]:font-sans [&_em]:text-[1.04em] [&_em]:font-normal [&_em]:tracking-[-0.01em] [&_em]:text-brand [&_em]:not-italic rtl:[&_em]:font-arabic">{!! $title !!}</h2>
            <p data-why-reveal class="{{ $reveal }} mx-auto max-w-[400px] text-[15px] leading-[1.65] text-ink-60 rtl:text-center">{{ $home->{"why_choose_body_{$locale}"} }}</p>
            @if (filled($ctaText))
                <div data-why-reveal class="{{ $reveal }} mt-8">
                    <x-website.button :href="$ctaUrl" arrow>{{ $ctaText }}</x-website.button>
                </div>
            @endif
        </header>

        <div class="mb-20 grid grid-cols-4 gap-px overflow-hidden rounded-xl bg-[rgba(13,13,13,0.08)] dark:bg-[rgba(237,232,223,0.04)] max-[900px]:grid-cols-2">
            @foreach ($stats as [$value, $suffix, $label])
                <div data-why-reveal data-stat class="{{ $reveal }} relative bg-[rgba(250,248,245,0.7)] px-7 py-8 text-center backdrop-blur-[8px] after:absolute after:bottom-0 after:left-1/2 after:h-0.5 after:w-8 after:-translate-x-1/2 after:rounded-[2px] after:bg-brand after:opacity-0 after:transition-opacity after:duration-600 dark:bg-[rgba(20,17,10,0.96)] dark:backdrop-blur-[16px]">
                    <span data-target="{{ $value }}" data-suffix="{{ $suffix }}" dir="ltr" class="mb-2 block font-sans text-[clamp(36px,4vw,52px)] leading-none font-light tracking-[-0.03em] text-brand">0{{ $suffix }}</span>
                    <span class="block text-[11px] font-medium tracking-[0.16em] text-ink uppercase dark:text-ink-60 rtl:tracking-[0.05em] rtl:normal-case">{{ $label }}</span>
                </div>
            @endforeach
        </div>

        <div class="relative">
            @include('website.home.why-path')

            <div class="grid min-h-[500px] grid-cols-4 grid-rows-[1fr_auto_1fr] gap-0 max-[900px]:min-h-0! max-[900px]:grid-cols-2! max-[900px]:grid-rows-none! max-[900px]:gap-x-6! max-[900px]:gap-y-10! max-[480px]:grid-cols-1! max-[480px]:gap-9!">
                @foreach (array_slice($home->why_choose_cards, 0, 4) as $i => $card)
                    @php($top = $i % 2 === 1)
                    @php($column = ['col-start-1 delay-0', 'col-start-2 delay-150', 'col-start-3 delay-300', 'col-start-4 delay-450'][$i])
                    <div
                        data-stage
                        @class([
                            $column,
                            'flex translate-y-[30px] flex-col items-center px-5 text-center opacity-0 transition-[opacity,translate] duration-700 max-[900px]:col-auto! max-[900px]:row-auto! max-[900px]:justify-start! max-[900px]:px-4! max-[900px]:py-0! max-[480px]:items-center! max-[480px]:px-6! max-[480px]:text-center!',
                            'row-start-1 justify-end pb-20' => $top,
                            'row-start-3 justify-start pt-12' => ! $top,
                        ])
                    >
                        @if ($card['icon'] ?? null)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($card['icon']) }}" alt="" loading="lazy" class="mb-5 h-[100px] w-[120px] shrink-0 object-contain max-[480px]:mb-[14px] max-[480px]:h-[66px] max-[480px]:w-20" />
                        @else
                            <x-dynamic-component :component="'website.illustrations.'.$illustrations[$i]" class="mb-5 h-[100px] w-[120px] shrink-0 max-[480px]:mb-[14px] max-[480px]:h-[66px] max-[480px]:w-20" />
                        @endif
                        <span class="mb-2 font-sans text-[10px] font-semibold tracking-[0.22em] text-brand uppercase dark:text-ink-60">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <h3 class="mb-[10px] font-sans text-[18px] font-normal tracking-[-0.01em] text-ink rtl:font-arabic">{{ $card["header_{$locale}"] ?? '' }}</h3>
                        <p class="max-w-[200px] text-[13.5px] leading-[1.65] text-ink-60 rtl:text-center">{{ $card["body_{$locale}"] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
