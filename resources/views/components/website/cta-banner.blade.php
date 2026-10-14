{{--
    The design's CTA banner (.cta-banner, projects.html): eyebrow, title and text on one side; call, WhatsApp and a
    third card on the other. Phone, WhatsApp and the working days / hours come from Settings → Business Info.
    The third card opens the footer's "Property Matching" form unless another link is given.
--}}
@props(['eyebrow', 'title', 'description', 'searchUrl' => '#property-matching'])

@php
    use Illuminate\Support\Carbon;

    $business = app(\Modules\GeneralSettings\Settings\BusinessInfoSettings::class);
    $whatsapp = $business->whatsapp ? 'https://wa.me/'.preg_replace('/\D/', '', $business->whatsapp) : null;
    $day = fn (string $day) => Carbon::parse($day)->locale(app()->getLocale())->dayName;
    $time = fn (string $time) => Carbon::createFromFormat('H:i', $time)->locale(app()->getLocale())->translatedFormat('g A');
    $hours = __(':from – :to, :open – :close', [
        'from' => $day($business->working_days_from),
        'to' => $day($business->working_days_to),
        'open' => $time($business->working_hours_from),
        'close' => $time($business->working_hours_to),
    ]);
    $cards = array_filter([
        $business->hotline ? ['href' => 'tel:'.$business->hotline, 'track' => 'phone', 'label' => __('Speak to an Expert'), 'sub' => $hours, 'icon' => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.63A2 2 0 012 1h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.09a16 16 0 006 6l1.27-.73a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 15.5v1.42z" /></svg>'] : null,
        $whatsapp ? ['href' => $whatsapp, 'track' => 'whatsapp', 'label' => __('WhatsApp Us'), 'sub' => __('Get a response within minutes'), 'icon' => '<svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" /></svg>'] : null,
        ['href' => $searchUrl, 'track' => null, 'label' => __('Request Custom Search'), 'sub' => __('Tell us exactly what you need'), 'icon' => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8" /><line x1="4" y1="6" x2="20" y2="6" /><line x1="8" y1="12" x2="16" y2="12" /><line x1="11" y1="18" x2="13" y2="18" /></svg>'],
    ]);
@endphp

<section {{ $attributes->class('relative overflow-hidden bg-[linear-gradient(135deg,rgb(13,11,8)_0%,rgb(17,14,10)_60%,rgb(22,12,13)_100%)] px-12 py-[100px] before:pointer-events-none before:absolute before:inset-0 before:bg-[radial-gradient(60%_80%_at_0%_50%,rgba(200,16,46,0.1)_0%,transparent_70%)] after:pointer-events-none after:absolute after:inset-0 after:z-0 after:bg-[radial-gradient(40%_60%_at_100%_50%,rgba(200,16,46,0.05)_0%,transparent_70%)] max-md:px-6 max-md:py-[60px]') }} aria-label="{{ __('Get in touch') }}">
    <div class="relative z-1 mx-auto grid max-w-[1320px] grid-cols-2 items-center gap-20 max-md:grid-cols-1 max-md:gap-10">
        <div>
            <span class="mb-6 flex items-center gap-3 text-[10px] font-bold tracking-[0.22em] text-brand uppercase dark:text-ink-60 rtl:font-arabic">{{ $eyebrow }}</span>
            <h2 class="mb-5 font-sans text-[clamp(2.2rem,4.5vw,3.8rem)] leading-[1.1] font-light text-white rtl:font-arabic [&_em]:text-brand [&_em]:not-italic">{!! $title !!}</h2>
            <p class="text-[15px] leading-[1.8] text-white/50">{{ $description }}</p>
        </div>
        <div class="flex flex-col gap-[10px]">
            @foreach ($cards as $card)
                <a
                    href="{{ $card['href'] }}"
                    @if (str_starts_with($card['href'], 'https://')) target="_blank" rel="noopener" @endif
                    @if ($card['track']) @click="$track(@js($card['track']))" @endif
                    class="group/cta flex items-center gap-[18px] rounded-2xl border border-white/9 bg-white/4 px-[22px] py-5 backdrop-blur-[24px] [transition:background_0.28s_var(--ease-out-ex),border-color_0.28s,translate_0.28s_var(--ease-out-ex)] hover:translate-x-[5px] hover:border-[rgba(200,16,46,0.32)] hover:bg-[rgba(200,16,46,0.1)] rtl:hover:-translate-x-[5px] max-md:hover:translate-x-0!"
                >
                    <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-[rgba(200,16,46,0.14)] text-brand transition-[background] duration-280 group-hover/cta:bg-[rgba(200,16,46,0.24)]" aria-hidden="true">{!! $card['icon'] !!}</span>
                    <span class="min-w-0 flex-1">
                        <span class="mb-[3px] block text-[13px] font-semibold text-white rtl:font-arabic">{{ $card['label'] }}</span>
                        <span class="block text-[11px] tracking-[0.02em] text-white/38">{{ $card['sub'] }}</span>
                    </span>
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" class="shrink-0 text-white/25 [transition:color_0.22s,translate_0.22s_var(--ease-out-ex)] group-hover/cta:translate-x-[3px] group-hover/cta:text-brand rtl:-scale-x-100 rtl:group-hover/cta:-translate-x-[3px]" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12" /><polyline points="12 5 19 12 12 19" /></svg>
                </a>
            @endforeach
        </div>
    </div>
</section>
