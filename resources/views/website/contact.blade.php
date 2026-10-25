{{--
    Contact page (the design's contact.html): hero, the "Send Us a Message" form beside the office map and contact
    cards, then the CTA banner. Phone, e-mail, WhatsApp, hours and the office address come from Settings → Business Info;
    a card whose setting is empty is left out.
--}}
@php
    use Illuminate\Support\Carbon;

    $business = app(\Modules\GeneralSettings\Settings\BusinessInfoSettings::class);
    $reveal = 'opacity-0 [transform:translateY(32px)] transition-[opacity,transform] duration-900 ease-out-ex [&.is-in]:opacity-100 [&.is-in]:[transform:none]';
    $intersect = 'x-intersect.once.threshold.12="$el.classList.add(\'is-in\')"';
    $office = $business->{'headquarters_'.app()->getLocale()};
    $time = fn (string $time) => Carbon::createFromFormat('H:i', $time)->locale(app()->getLocale())->translatedFormat('g A');
    $hours = __('Available :open – :close', ['open' => $time($business->working_hours_from), 'close' => $time($business->working_hours_to)]);
    $cards = array_filter([
        $business->hotline ? ['href' => 'tel:'.preg_replace('/[^\d+]/', '', $business->hotline), 'track' => 'phone', 'label' => __('Phone'), 'value' => $business->hotline, 'ltr' => true, 'icon' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13 19.79 19.79 0 0 1 1.61 4.35 2 2 0 0 1 3.6 2.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.29 6.29l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" />'] : null,
        $business->email ? ['href' => 'mailto:'.$business->email, 'track' => null, 'label' => __('Email'), 'value' => $business->email, 'ltr' => true, 'icon' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" /><polyline points="22,6 12,13 2,6" />'] : null,
        $business->whatsapp ? ['href' => 'https://wa.me/'.preg_replace('/\D/', '', $business->whatsapp), 'track' => 'whatsapp', 'label' => __('WhatsApp'), 'value' => $hours, 'ltr' => false, 'icon' => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />'] : null,
        $office ? ['href' => null, 'track' => null, 'label' => __('Office'), 'value' => $office, 'ltr' => false, 'icon' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" /><circle cx="12" cy="10" r="3" />'] : null,
    ]);
    $card = 'flex items-start gap-[14px] rounded-[14px] border border-ink-10 bg-white p-5 dark:border-white/8 dark:bg-[#1a1916]';
    $hover = 'group/card [transition:border-color_0.25s,box-shadow_0.25s,translate_0.25s_var(--ease-out-ex)] hover:-translate-y-0.5 hover:border-[rgba(200,16,46,0.25)] hover:shadow-[0_8px_32px_rgba(200,16,46,0.08)]';
@endphp

<x-website.layout :title="__('Contact Us')">
    {{-- Hero --}}
    @push('head')
        <link rel="preload" as="image" href="{{ asset('images/website/contact-hero-small.webp') }}" media="(max-width: 768px)" fetchpriority="high" />
        <link rel="preload" as="image" href="{{ asset('images/website/contact-hero.webp') }}" media="(min-width: 769px)" fetchpriority="high" />
    @endpush
    <section
        x-data
        x-init="const bg = $refs.bg; const move = () => window.scrollY < window.innerHeight && (bg.style.transform = `translateY(${window.scrollY * 0.35}px)`); window.addEventListener('scroll', move, { passive: true }); move()"
        class="relative flex h-screen min-h-[680px] flex-col overflow-hidden bg-[#07060a] max-md:h-auto max-md:min-h-0"
        aria-label="{{ __('Contact Akar Town') }}"
    >
        <div x-ref="bg" class="absolute inset-0 bg-[#07060a] bg-(image:--bg-small) bg-cover bg-center bg-no-repeat brightness-50 grayscale-25 transition-transform duration-50 ease-linear will-change-transform min-[769px]:bg-(image:--bg)" style="--bg: url('{{ asset('images/website/contact-hero.webp') }}'); --bg-small: url('{{ asset('images/website/contact-hero-small.webp') }}')" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-0 z-1 bg-(image:--grain) bg-size-[200px_auto] opacity-4" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-0 z-2 bg-[radial-gradient(ellipse_75%_75%_at_50%_45%,rgba(7,6,10,0.35)_0%,rgba(7,6,10,0.72)_100%),linear-gradient(to_bottom,rgba(7,6,10,0.65)_0%,rgba(7,6,10,0.22)_40%,rgba(7,6,10,0.68)_100%)]" aria-hidden="true"></div>

        <div {!! $intersect !!} class="{{ $reveal }} relative z-5 flex flex-1 flex-col items-center justify-center px-12 pt-nav pb-5 text-center max-[1100px]:px-6 max-md:px-5 max-md:pt-[calc(var(--spacing-nav)+48px)] max-md:pb-16">
            <span class="mb-7 text-[9px] font-bold tracking-[0.35em] text-brand uppercase rtl:font-arabic rtl:tracking-normal">{{ __("We'd Love to Hear From You") }}</span>
            @php($rule = 'mb-8 flex w-full max-w-[700px] items-center gap-[18px] before:h-px before:flex-1 before:bg-[linear-gradient(to_right,transparent,rgba(255,255,255,0.18),transparent)] after:h-px after:flex-1 after:bg-[linear-gradient(to_right,transparent,rgba(255,255,255,0.18),transparent)]')
            <div class="{{ $rule }}" aria-hidden="true"><div class="size-[7px] shrink-0 rotate-45 border border-[rgba(200,16,46,0.7)] bg-[rgba(200,16,46,0.15)]"></div></div>
            <h1 class="mb-8 font-sans text-[clamp(62px,8.5vw,128px)] leading-[0.96] font-light tracking-[-0.028em] text-white rtl:font-arabic rtl:leading-[1.25] rtl:tracking-normal max-md:text-[clamp(46px,10vw,72px)] [&_em]:block [&_em]:text-white/58 [&_em]:not-italic">{!! __("Let's Start a <em>Conversation</em>") !!}</h1>
            <div class="{{ $rule }}" aria-hidden="true"><div class="size-[7px] shrink-0 rotate-45 border border-[rgba(200,16,46,0.7)] bg-[rgba(200,16,46,0.15)]"></div></div>
            <p class="max-w-[50ch] text-[clamp(14px,1.1vw,16px)] leading-[1.85] font-light text-white/48 rtl:font-arabic">{{ __("Whether you're searching for your first home or expanding your investment portfolio — our experts are here to guide every step.") }}</p>
        </div>
    </section>

    {{-- Form + map --}}
    <section id="contact" x-data class="relative overflow-hidden bg-page py-[clamp(80px,10vw,130px)] before:absolute before:inset-x-12 before:top-0 before:h-px before:bg-[linear-gradient(to_right,var(--color-brand)_0%,rgba(200,16,46,0.18)_45%,transparent_100%)] max-[1100px]:before:inset-x-6 rtl:before:bg-[linear-gradient(to_left,var(--color-brand)_0%,rgba(200,16,46,0.18)_45%,transparent_100%)] dark:bg-[#100f0d]" aria-label="{{ __('Contact Akar Town') }}">
        <div class="mx-auto grid max-w-[1320px] grid-cols-2 items-start gap-[clamp(48px,7vw,90px)] px-12 max-[1100px]:gap-10 max-[1100px]:px-6 max-[860px]:grid-cols-1 max-md:px-5">
            <div {!! $intersect !!} class="{{ $reveal }}">
                <h2 class="mb-3 font-sans text-[clamp(36px,4.5vw,62px)] leading-[1.06] font-light tracking-[-0.022em] text-ink rtl:font-arabic rtl:leading-[1.3] rtl:tracking-normal [&_em]:text-brand [&_em]:not-italic">{!! __('Send Us a <em>Message</em>') !!}</h2>
                <p class="mb-10 max-w-[38ch] text-[15px] leading-[1.75] text-ink-60 rtl:font-arabic">{{ __('Fill out the form and a member of our team will be in touch within one business day.') }}</p>
                <livewire:website.contact-form />
            </div>

            <div {!! $intersect !!} class="{{ $reveal }} flex flex-col gap-7 delay-120 max-[860px]:-order-1">
                @if ($office)
                    <div class="relative aspect-[4/3.2] overflow-hidden rounded-[20px] bg-[#e8e4de] shadow-[0_2px_6px_rgba(13,13,13,0.04),0_12px_40px_rgba(13,13,13,0.1)] dark:shadow-[0_2px_6px_rgba(0,0,0,0.2),0_12px_40px_rgba(0,0,0,0.4)]">
                        <iframe
                            src="https://maps.google.com/maps?q={{ urlencode($business->headquarters_en) }}&hl={{ app()->getLocale() }}&z=14&output=embed"
                            class="block size-full border-0 saturate-80 contrast-105"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="{{ __('Akar Town Office Location') }}"
                            allowfullscreen
                        ></iframe>
                        <div class="pointer-events-none absolute start-4 top-4 flex items-center gap-2 rounded-full bg-white/92 px-4 py-2 text-[11px] font-semibold text-ink shadow-[0_4px_16px_rgba(13,13,13,0.12)] backdrop-blur-[16px] rtl:font-arabic dark:bg-[rgba(26,25,22,0.92)]" aria-hidden="true">
                            <span class="size-[7px] shrink-0 animate-pulse-dot rounded-full bg-brand"></span>
                            {{ __('Akar Town HQ') }}
                        </div>
                    </div>
                @endif

                @if ($cards)
                    <div class="grid grid-cols-2 gap-4 max-[520px]:grid-cols-1">
                        @foreach ($cards as $item)
                            <{{ $item['href'] ? 'a' : 'div' }}
                                @if ($item['href']) href="{{ $item['href'] }}" @endif
                                @if ($item['href'] && str_starts_with($item['href'], 'https://')) target="_blank" rel="noopener noreferrer" @endif
                                @if ($item['track']) @click="$track(@js($item['track']))" @endif
                                class="{{ $card }} {{ $item['href'] ? $hover : '' }}"
                            >
                                <span class="grid size-[38px] shrink-0 place-items-center rounded-[10px] bg-[rgba(200,16,46,0.08)] text-brand transition-[background] duration-250 group-hover/card:bg-[rgba(200,16,46,0.16)]" aria-hidden="true">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $item['icon'] !!}</svg>
                                </span>
                                <span class="min-w-0">
                                    <span class="mb-1 block text-[9px] font-bold tracking-[0.2em] text-ink-30 uppercase rtl:font-arabic rtl:tracking-normal">{{ $item['label'] }}</span>
                                    <span class="block text-[13px] leading-[1.4] font-semibold break-words text-ink rtl:font-arabic" @if ($item['ltr']) dir="ltr" @endif>{{ $item['value'] }}</span>
                                </span>
                            </{{ $item['href'] ? 'a' : 'div' }}>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    <x-website.cta-banner
        :eyebrow="__('Begin Your Journey')"
        :title="__('Can’t Find Your<br><em>Dream</em> Property?')"
        :description="__('Our expert consultants are ready to guide you through Egypt’s finest developments — personally, and at your pace.')"
        :call-label="__('Call Us Directly')"
        call-with-number
        :whatsapp-sub="__('Instant replies · Send photos & requirements')"
        :search-url="route('properties')"
        :search-label="__('Browse All Properties')"
        :search-sub="__('Explore curated listings across Egypt')"
        search-icon="grid"
    />
</x-website.layout>
