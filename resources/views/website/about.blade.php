{{--
    About page (the design's about.html): hero, the story beside the round photo (Settings → About Us, else the design's photo) with the years badge, then — not in the
    design, the user asked for every About Us setting to show — the four numbers and the core values, then the CTA
    banner. Texts, numbers and values come from Settings → About Us.
--}}
@php
    $about = app(\Modules\GeneralSettings\Settings\AboutUsSettings::class);
    $locale = app()->getLocale();
    $reveal = 'opacity-0 [transform:translateY(32px)] transition-[opacity,transform] duration-900 ease-out-ex [&.is-in]:opacity-100 [&.is-in]:[transform:none]';
    $intersect = 'x-intersect.once.threshold.12="$el.classList.add(\'is-in\')"';

    // The design colours the end of each title: the last two words of the hero, the last word of the story.
    $emphasise = function (string $text, int $words): string {
        $parts = preg_split('/\s+/u', trim($text));
        if (count($parts) <= $words) {
            return '<em>'.e(trim($text)).'</em>';
        }

        return e(implode(' ', array_slice($parts, 0, -$words))).' <em>'.e(implode(' ', array_slice($parts, -$words))).'</em>';
    };
    // The story is plain text: a blank line starts a new paragraph.
    $paragraphs = array_filter(array_map('trim', preg_split('/\R\s*\R/u', (string) $about->{"story_body_{$locale}"})));
    $stats = [
        [$about->stats_happy_clients, '+', __('Happy Clients')],
        [$about->stats_properties_sold, '+', __('Properties Sold')],
        [$about->stats_awards_won, '+', __('Awards Won')],
        [$about->stats_commitment, '%', __('Commitment')],
    ];
    $values = array_values(array_filter($about->core_values, fn (array $value) => filled($value["header_{$locale}"] ?? null)));
@endphp

<x-website.layout :title="__('About Us')" :description="$about->{'hero_body_'.$locale}">
    <x-website.statement-hero
        :eyebrow="__('Egypt’s Premier Luxury Real Estate')"
        :title="$emphasise($about->{'hero_header_'.$locale}, 2)"
        :description="$about->{'hero_body_'.$locale}"
        :image="asset('images/website/about-hero.webp')"
        :image-small="asset('images/website/about-hero-small.webp')"
        :label="__('About Akar Town')"
        brightness="brightness-52 grayscale-20"
        em-block
    />

    {{-- Story --}}
    <section x-data class="relative overflow-hidden bg-page py-[clamp(80px,10vw,130px)] before:absolute before:inset-x-12 before:top-0 before:h-px before:bg-[linear-gradient(to_right,var(--color-brand)_0%,rgba(200,16,46,0.18)_45%,transparent_100%)] max-[1100px]:before:inset-x-6 rtl:before:bg-[linear-gradient(to_left,var(--color-brand)_0%,rgba(200,16,46,0.18)_45%,transparent_100%)] dark:bg-[#100f0d]" aria-label="{{ __('Our Story') }}">
        <div class="mx-auto grid max-w-[1320px] grid-cols-2 items-center gap-[clamp(48px,7vw,100px)] px-12 max-[1100px]:gap-10 max-[1100px]:px-6 max-[860px]:grid-cols-1 max-md:px-5">
            <div {!! $intersect !!} class="{{ $reveal }}">
                <h2 class="mb-7 font-sans text-[clamp(44px,5.5vw,80px)] leading-[1.06] font-light tracking-[-0.022em] text-ink rtl:font-arabic rtl:leading-[1.3] rtl:tracking-normal [&_em]:text-brand [&_em]:not-italic">{!! $emphasise($about->{'story_header_'.$locale}, 1) !!}</h2>
                @foreach ($paragraphs as $paragraph)
                    <p class="mb-10 max-w-[42ch] text-[16px] leading-[1.85] text-ink-60 rtl:font-arabic">{{ $paragraph }}</p>
                @endforeach
                <x-website.button :href="route('properties')" arrow>{{ __('Explore Properties') }}</x-website.button>
            </div>

            <div {!! $intersect !!} class="{{ $reveal }} flex items-center justify-center delay-120">
                <div class="relative">
                    <div class="pointer-events-none absolute -inset-4 animate-ring-pulse rounded-full border border-[rgba(200,16,46,0.22)]" aria-hidden="true"></div>
                    <div class="group/photo relative size-[clamp(300px,36vw,460px)] shrink-0 overflow-hidden rounded-full max-[860px]:size-[clamp(260px,72vw,380px)]">
                        <img src="{{ $about->story_image ? \Illuminate\Support\Facades\Storage::disk('public')->url($about->story_image) : asset('images/website/about-story.webp') }}" alt="{{ __('Luxury property in Egypt') }}" loading="lazy" width="460" height="460" class="block size-full object-cover transition-transform duration-900 ease-out-ex group-hover/photo:scale-106" />
                    </div>
                    <div class="absolute right-[-16px] bottom-3 flex flex-col items-center gap-0.5 rounded-full border border-ink-10 bg-white px-5 py-3 whitespace-nowrap shadow-[0_8px_32px_rgba(13,13,13,0.1)] max-[860px]:right-0 max-[860px]:bottom-2 dark:border-white/10 dark:bg-[#1a1916]">
                        <span dir="ltr" class="font-sans text-[28px] leading-none font-light tracking-[-0.025em] text-brand">{{ $about->years_of_excellence }}+</span>
                        <span class="text-[8px] font-bold tracking-[0.18em] text-ink-30 uppercase rtl:font-arabic rtl:tracking-normal dark:text-white/40">{{ __('Years of Excellence') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Numbers (the home "Why Choose Us" strip's look), counted up when they show --}}
    <section x-data class="bg-page pb-[clamp(64px,8vw,110px)] dark:bg-[#100f0d]" aria-label="{{ __('Akar Town in numbers') }}">
        <div {!! $intersect !!} class="{{ $reveal }} mx-auto max-w-[1320px] px-12 max-[1100px]:px-6 max-md:px-5">
            <div class="grid grid-cols-4 gap-px overflow-hidden rounded-xl bg-[rgba(13,13,13,0.08)] dark:bg-[rgba(237,232,223,0.04)] max-[900px]:grid-cols-2">
                @foreach ($stats as [$value, $suffix, $label])
                    <div
                        x-data="{ shown: 0 }"
                        x-intersect.once.threshold.40="const start = performance.now(); const tick = (now) => { const p = Math.min((now - start) / 1800, 1); shown = Math.round((1 - Math.pow(1 - p, 3)) * {{ (int) $value }}); if (p < 1) requestAnimationFrame(tick) }; requestAnimationFrame(tick)"
                        class="bg-white px-7 py-8 text-center max-md:px-4 dark:bg-[rgba(20,17,10,0.96)]"
                    >
                        <span dir="ltr" class="mb-2 block font-sans text-[clamp(36px,4vw,52px)] leading-none font-light tracking-[-0.03em] text-brand" x-text="(shown >= 1000 ? (shown / 1000).toFixed(1).replace(/\.0$/, '') + 'K' : shown) + @js($suffix)">{{ $value }}{{ $suffix }}</span>
                        <span class="block text-[11px] font-medium tracking-[0.16em] text-ink uppercase rtl:font-arabic rtl:tracking-normal dark:text-ink-60">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Core values (the contact cards' look) --}}
    @if ($values)
        <section x-data class="relative bg-page pb-[clamp(80px,10vw,130px)] dark:bg-[#100f0d]" aria-labelledby="values-title">
            <div class="mx-auto max-w-[1320px] px-12 max-[1100px]:px-6 max-md:px-5">
                <div {!! $intersect !!} class="{{ $reveal }} mb-10 text-center">
                    <p class="mb-4 text-[10px] font-bold tracking-[0.26em] text-brand uppercase rtl:font-arabic rtl:tracking-normal">{{ __('What We Stand For') }}</p>
                    <h2 id="values-title" class="font-sans text-[clamp(32px,3.6vw,52px)] leading-[1.1] font-light tracking-[-0.02em] text-ink rtl:font-arabic rtl:leading-[1.3] rtl:tracking-normal [&_em]:text-brand [&_em]:not-italic">{!! __('Our Core <em>Values</em>') !!}</h2>
                </div>
                <div class="grid grid-cols-3 gap-5 max-[960px]:grid-cols-2 max-[600px]:grid-cols-1">
                    @foreach ($values as $value)
                        <div {!! $intersect !!} class="{{ $reveal }} rounded-2xl border border-ink-10 bg-white p-7 [transition:opacity_0.9s,transform_0.9s,border-color_0.25s,box-shadow_0.25s] hover:border-[rgba(200,16,46,0.25)] hover:shadow-[0_8px_32px_rgba(200,16,46,0.08)] dark:border-white/8 dark:bg-[#1a1916]" style="transition-delay: {{ $loop->index * 0.12 }}s">
                            <span class="mb-5 grid size-14 place-items-center overflow-hidden rounded-xl bg-[rgba(200,16,46,0.08)]" aria-hidden="true">
                                @if ($value['icon'] ?? null)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($value['icon']) }}" alt="" loading="lazy" class="size-9 object-contain" />
                                @else
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="text-brand"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" /></svg>
                                @endif
                            </span>
                            <h3 class="mb-2.5 text-[18px] font-semibold tracking-[-0.01em] text-ink rtl:font-arabic rtl:tracking-normal">{{ $value["header_{$locale}"] }}</h3>
                            <p class="text-[14px] leading-[1.75] text-ink-60 rtl:font-arabic">{{ $value["body_{$locale}"] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-website.cta-banner
        :eyebrow="__('Begin Your Journey')"
        :title="__('Can’t Find Your<br><em>Dream</em> Property?')"
        :description="__('Our expert consultants are ready to guide you through Egypt’s finest developments — personally, and at your pace.')"
        variant="contact"
    />
</x-website.layout>
