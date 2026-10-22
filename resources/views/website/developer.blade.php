{{--
    One developer (the design's developer-details.html): hero (its photo with parallax, logo in breathing rings, name,
    tagline, phone / e-mail / address), then its story (a project photo, "Follow us on" links, "About <name>" and the
    description), then its projects (not in the design; user asked) with "View All" to the filtered projects listing.
--}}
@php
    $reveal = 'opacity-0 [transform:translateY(32px)] transition-[opacity,transform] duration-900 ease-out-ex [&.is-in]:opacity-100 [&.is-in]:[transform:none]';
    $intersect = 'x-intersect.once.threshold.12="$el.classList.add(\'is-in\')"';
    $badge = 'inline-flex items-center gap-1.5 rounded-full border border-white/12 bg-white/5.5 py-1.5 ps-2.5 pe-3.5 text-[10px] font-semibold tracking-[0.09em] text-white/62 uppercase backdrop-blur-[12px] transition-[border-color,background] duration-250 hover:border-white/22 hover:bg-white/9 max-[600px]:py-[5px] max-[600px]:ps-2 max-[600px]:pe-[11px] max-[600px]:text-[9px] rtl:font-arabic rtl:tracking-normal';
    $badgeIcon = 'size-3.5 shrink-0 opacity-80';
    $icons = [
        'instagram' => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>',
        'facebook' => '<svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>',
        'linkedin' => '<svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>',
        'youtube' => '<svg width="14" height="10" viewBox="0 0 24 17" fill="currentColor" aria-hidden="true"><path d="M22.54 2.42A2.78 2.78 0 0 0 20.59.46C18.88 0 12 0 12 0S5.12 0 3.41.46A2.78 2.78 0 0 0 1.46 2.42 29 29 0 0 0 1 8.5a29 29 0 0 0 .46 6.08 2.78 2.78 0 0 0 1.95 1.96C5.12 17 12 17 12 17s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.96A29 29 0 0 0 23 8.5a29 29 0 0 0-.46-6.08zM9.75 12.02V4.98L15.5 8.5l-5.75 3.52z"/></svg>',
    ];
    $networks = ['instagram' => 'Instagram', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube'];
@endphp

<x-website.layout :title="$metaTitle" :description="$metaDescription">
    {{-- Hero --}}
    <section
        x-data
        x-init="const bg = $refs.bg; const move = () => (bg.style.transform = `translateY(${window.scrollY * 0.3}px)`); window.addEventListener('scroll', move, { passive: true }); move()"
        class="relative flex flex-col overflow-hidden bg-[#07060a]"
        aria-label="{{ __('Developer profile hero') }}"
    >
        {{-- Phones take the card size; the photo is asked for early (it's the page's biggest thing). --}}
        @if ($cover)
            @push('head')
                <link rel="preload" as="image" href="{{ $coverSmall }}" media="(max-width: 768px)" fetchpriority="high" />
                <link rel="preload" as="image" href="{{ $cover }}" media="(min-width: 769px)" fetchpriority="high" />
            @endpush
        @endif
        <div x-ref="bg" class="absolute inset-0 bg-[#07060a] bg-(image:--bg-small) bg-cover bg-center bg-no-repeat brightness-38 grayscale-20 will-change-transform min-[769px]:bg-(image:--bg)" @if ($cover) style="--bg: url('{{ $cover }}'); --bg-small: url('{{ $coverSmall }}')" @endif aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-0 z-1 bg-(image:--grain) bg-size-[200px_auto] opacity-[0.038]" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-0 z-2 bg-[radial-gradient(ellipse_65%_65%_at_50%_42%,rgba(7,6,10,0.22)_0%,rgba(7,6,10,0.7)_100%),linear-gradient(to_bottom,rgba(7,6,10,0.78)_0%,rgba(7,6,10,0.12)_40%,rgba(7,6,10,0.85)_100%)]" aria-hidden="true"></div>
        <div class="pointer-events-none absolute top-1/2 left-1/2 z-3 size-[380px] -translate-x-1/2 -translate-y-[58%] rounded-full bg-[radial-gradient(circle,rgba(200,16,46,0.11)_0%,transparent_68%)]" aria-hidden="true"></div>

        <nav class="absolute top-[calc(var(--spacing-nav)+26px)] left-12 z-6 flex items-center gap-2 text-[10px] tracking-[0.2em] text-white/45 uppercase rtl:right-12 rtl:left-auto rtl:font-arabic rtl:tracking-normal max-[900px]:left-6 max-[900px]:rtl:right-6" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('home') }}" class="text-white/45 transition-colors duration-200 hover:text-brand">{{ __('Home') }}</a>
            <span class="text-white/22" aria-hidden="true">›</span>
            <a href="{{ route('developers') }}" class="text-white/45 transition-colors duration-200 hover:text-brand">{{ __('Developers') }}</a>
            <span class="text-white/22" aria-hidden="true">›</span>
            <span class="text-white/82">{{ $name }}</span>
        </nav>

        <div class="relative z-5 flex flex-1 flex-col">
            <div class="flex flex-1 flex-col items-center justify-center px-20 pt-[calc(var(--spacing-nav)+80px)] pb-20 text-center max-md:px-6 max-md:pb-14">
                {{-- Logo in breathing rings --}}
                <div class="relative mb-2.5 flex size-[100px] items-center justify-center before:absolute before:-inset-3 before:animate-ring-breathe before:rounded-full before:border before:border-[rgba(200,16,46,0.3)] after:absolute after:-inset-6 after:animate-ring-breathe after:rounded-full after:border after:border-[rgba(200,16,46,0.14)] after:[animation-delay:0.9s]" role="img" aria-label="{{ __(':name logo', ['name' => $name]) }}">
                    <div class="relative z-1 flex size-[100px] items-center justify-center overflow-hidden rounded-full border-[1.5px] border-[rgba(200,16,46,0.5)] bg-[rgba(13,11,8,0.75)] shadow-[0_0_0_1px_rgba(255,255,255,0.06),0_8px_40px_rgba(0,0,0,0.5),inset_0_1px_0_rgba(255,255,255,0.08)] backdrop-blur-[24px] backdrop-saturate-160">
                        @if ($logo)
                            <img src="{{ $logo }}" alt="" class="size-full object-contain p-3" />
                        @else
                            <span class="font-sans text-[32px] leading-none font-light tracking-[0.04em] text-white select-none" aria-hidden="true">{{ $initials }}</span>
                        @endif
                    </div>
                </div>

                <div class="mb-6 flex w-full max-w-[380px] items-center gap-4 before:h-px before:flex-1 before:bg-[linear-gradient(to_right,transparent,rgba(255,255,255,0.15),transparent)] after:h-px after:flex-1 after:bg-[linear-gradient(to_right,transparent,rgba(255,255,255,0.15),transparent)]" aria-hidden="true">
                    <span class="size-1.5 shrink-0 rotate-45 border border-[rgba(200,16,46,0.65)] bg-[rgba(200,16,46,0.12)]"></span>
                </div>

                <h1 class="mb-1.5 block font-sans text-[clamp(32px,4.5vw,60px)] leading-[0.9] font-light tracking-[0.01em] text-white rtl:font-arabic rtl:leading-[1.2] max-[420px]:tracking-[-0.01em]">{{ $name }}</h1>
                @if ($tagline)
                    <p class="mb-[30px] max-w-[44ch] text-[clamp(13px,0.95vw,15px)] leading-[1.8] font-light tracking-[0.03em] text-white/42 max-[420px]:hidden">{{ $tagline }}</p>
                @endif

                @if ($phone || $email || $address)
                    <div class="flex flex-wrap items-center justify-center gap-[9px] max-[600px]:gap-1.5" role="list" aria-label="{{ __('Developer contact') }}">
                        @if ($phone)
                            <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="{{ $badge }}" role="listitem">
                                <svg class="{{ $badgeIcon }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.27h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.18 6.18l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" /></svg>
                                <span dir="ltr">{{ $phone }}</span>
                            </a>
                        @endif
                        @if ($email)
                            <a href="mailto:{{ $email }}" class="{{ $badge }} normal-case" role="listitem">
                                <svg class="{{ $badgeIcon }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" /><polyline points="22,6 12,13 2,6" /></svg>
                                {{ $email }}
                            </a>
                        @endif
                        @if ($address)
                            <span class="{{ $badge }}" role="listitem">
                                <svg class="{{ $badgeIcon }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z" /><circle cx="12" cy="10" r="3" /></svg>
                                {{ $address }}
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Story --}}
    <section id="story" x-data class="relative overflow-hidden bg-page pt-[clamp(96px,12vw,156px)] pb-[clamp(96px,12vw,140px)] before:absolute before:inset-x-12 before:top-0 before:z-1 before:h-px before:bg-[linear-gradient(to_right,var(--color-brand)_0%,rgba(200,16,46,0.18)_45%,transparent_100%)] after:pointer-events-none after:absolute after:inset-0 after:z-0 after:bg-(image:--grain) after:bg-size-[200px_auto] after:opacity-[0.028] rtl:before:bg-[linear-gradient(to_left,var(--color-brand)_0%,rgba(200,16,46,0.18)_45%,transparent_100%)]" aria-label="{{ __('Developer identity and story') }}">
        <div class="relative z-2 mx-auto grid max-w-[1320px] grid-cols-[1.1fr_1fr] items-center gap-20 px-12 max-[960px]:grid-cols-1 max-[960px]:gap-0 max-[600px]:px-6">
            {{-- Photo + social links --}}
            <div {!! $intersect !!} class="{{ $reveal }} relative z-0 max-[960px]:mx-auto max-[960px]:mb-[72px] max-[960px]:w-full max-[960px]:max-w-[520px]">
                <div class="pointer-events-none absolute -top-3.5 right-3.5 bottom-3.5 -left-3.5 z-0 rounded-[20px] border border-[rgba(200,16,46,0.22)] dark:border-[rgba(200,16,46,0.12)] rtl:right-[-14px] rtl:left-3.5 max-[960px]:hidden" aria-hidden="true"></div>
                <div class="group/photo relative z-1 aspect-[4/3] overflow-hidden rounded-2xl bg-ink-10 after:pointer-events-none after:absolute after:inset-0 after:z-2 after:bg-[linear-gradient(to_bottom,transparent_45%,rgba(7,6,10,0.5)_100%)]">
                    @if ($storyPhoto)
                        <img src="{{ $storyPhoto }}" alt="{{ __('A project by :name', ['name' => $name]) }}" loading="lazy" class="block size-full object-cover transition-transform duration-900 ease-out-ex group-hover/photo:scale-104" />
                    @endif
                </div>

                @if ($socials)
                    <div class="absolute bottom-9 -left-8 z-3 max-w-[260px] rounded-xl border border-s-2 border-white/7 border-s-brand bg-[rgba(7,6,10,0.86)] px-5 py-[18px] shadow-[0_12px_40px_rgba(0,0,0,0.38)] backdrop-blur-[24px] backdrop-saturate-140 rtl:-right-8 rtl:left-auto max-[960px]:-bottom-7 max-[960px]:left-0 max-[960px]:rtl:right-0 max-[600px]:max-w-[calc(100%-24px)]" aria-label="{{ __('Developer social media') }}">
                        <span class="mb-3.5 block text-[8.5px] font-bold tracking-[0.22em] text-white/35 uppercase rtl:font-arabic rtl:tracking-normal">{{ __('Follow us on') }}</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($socials as $network => $url)
                                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-[7px] rounded-lg border border-white/10 bg-white/4 py-[7px] ps-[9px] pe-3 text-[10.5px] font-medium tracking-[0.02em] whitespace-nowrap text-white/70 no-underline transition-[border-color,background,color] duration-250 hover:border-[rgba(200,16,46,0.45)] hover:bg-[rgba(200,16,46,0.1)] hover:text-white [&_svg]:shrink-0 [&_svg]:opacity-80" aria-label="{{ $networks[$network] }}">
                                    {!! $icons[$network] !!}
                                    {{ $networks[$network] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Text --}}
            <div class="flex flex-col justify-center">
                <p {!! $intersect !!} class="{{ $reveal }} mb-5 block text-[10px] font-bold tracking-[0.26em] text-brand uppercase rtl:font-arabic rtl:tracking-normal">{{ __('Our Story') }}</p>
                <h2 {!! $intersect !!} class="{{ $reveal }} mb-[22px] font-sans text-[clamp(36px,4.2vw,58px)] leading-[1.1] font-light tracking-[-0.01em] text-ink delay-120 rtl:font-arabic rtl:leading-[1.3] [&_em]:text-brand [&_em]:not-italic">{!! __('About <em>:name</em>', ['name' => e($name)]) !!}</h2>
                @if ($description)
                    <div {!! $intersect !!} class="{{ $reveal }} text-[15px] leading-[1.9] font-light text-ink-60 delay-240 [&_p]:mb-4 [&_ul]:mb-4 [&_ul]:list-disc [&_ul]:ps-5 [&_strong]:font-medium [&_strong]:text-ink">{!! $description !!}</div>
                @endif
            </div>
        </div>
    </section>

    {{-- Projects (not in the design) --}}
    @if ($projects)
        <section x-data class="border-t border-ink-10 bg-page pt-20 pb-[100px] max-md:pt-[60px] max-md:pb-20" aria-label="{{ __('Projects by :name', ['name' => $name]) }}">
            <div class="mx-auto max-w-[1320px] px-12 max-[1100px]:px-7 max-md:px-5">
                <header {!! $intersect !!} class="{{ $reveal }} mb-12 flex items-end justify-between gap-10 max-md:mb-8 max-md:flex-col max-md:items-start max-md:gap-4">
                    <div>
                        <p class="mb-4 text-[10px] font-bold tracking-[0.26em] text-brand uppercase rtl:font-arabic rtl:tracking-normal">{{ __('Portfolio') }}</p>
                        <h2 class="font-sans text-[clamp(30px,3.4vw,46px)] leading-[1.1] font-light tracking-[-0.01em] text-ink rtl:font-arabic [&_em]:text-brand [&_em]:not-italic">{!! __('Projects by <em>:name</em>', ['name' => e($name)]) !!}</h2>
                    </div>
                    @if ($total > count($projects))
                        <x-website.view-all :href="route('projects', ['developer' => $slug])">{{ __('View All Projects (:count)', ['count' => $total]) }}</x-website.view-all>
                    @endif
                </header>
                <div class="grid grid-cols-3 gap-7 max-xl:grid-cols-2 max-md:grid-cols-1">
                    @foreach ($projects as $i => $card)
                        <x-website.project-card :card="$card" :index="$i" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-website.layout>
