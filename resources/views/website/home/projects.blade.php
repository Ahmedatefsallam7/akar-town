{{--
    "Our Projects" (the design's .pgal): four featured projects in a mosaic (wide, tall, two squares) revealed with a
    wipe; on hover a details panel slides in from the card's natural edge. Behaviour: resources/js/website/projects.js.
    @var string $title  the home section's title (Sliders)
    @var list<array> $projects  App\Support\Website\FeaturedProjects::get()
--}}
@php
    $reveal = 'opacity-0 [transform:translateY(24px)]';
    $areas = [
        'col-span-2 col-start-1 row-start-1',
        'col-start-3 row-span-2 row-start-1',
        'col-start-1 row-start-2',
        'col-start-2 row-start-2',
    ];
    $action = 'flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-full border p-0 backdrop-blur-[14px] backdrop-saturate-160 [transition:background_0.24s_ease,border-color_0.24s_ease,color_0.24s_ease,box-shadow_0.24s_ease,scale_0.28s_cubic-bezier(0.16,1,0.3,1)] hover:scale-112 hover:border-white/96 hover:bg-white/96 hover:text-ink';
    $actionIcon = 'pointer-events-none size-[14px] fill-none stroke-current stroke-[1.7] transition-[fill] duration-200 [stroke-linecap:round] [stroke-linejoin:round]';
    $text = 'opacity-0';
@endphp

<section id="projects" x-data="projectsGrid" class="border-t border-ink-10 bg-page pt-20 pb-[100px]">
    <div class="mx-auto max-w-[1340px] px-[60px] max-[900px]:px-6 max-md:px-5">
        <header class="mb-[52px] flex items-end justify-between gap-10">
            <div class="flex-1">
                <p data-grid-reveal class="{{ $reveal }} mb-[22px] inline-flex items-center rounded-[20px] border border-white/65 bg-white/52 px-4 py-1.5 font-sans text-[9.5px] font-bold tracking-[0.3em] text-brand-dk uppercase shadow-[0_2px_16px_rgba(0,0,0,0.08),0_1px_4px_rgba(0,0,0,0.05)] backdrop-blur-[20px] backdrop-saturate-180 dark:text-ink-60 rtl:font-arabic">{{ __('Portfolio') }}</p>
                <h2 data-grid-reveal class="{{ $reveal }} m-0 mb-3 font-sans text-[clamp(34px,3.8vw,52px)] leading-[1.1] font-light tracking-[-0.02em] text-ink rtl:font-arabic [&_em]:font-sans [&_em]:text-[1.04em] [&_em]:font-normal [&_em]:tracking-[-0.01em] [&_em]:text-brand [&_em]:not-italic rtl:[&_em]:font-arabic">{!! \App\Support\Website\HomeSections::accent($title) !!}</h2>
                <p data-grid-reveal class="{{ $reveal }} max-w-[380px] text-[15px] leading-[1.65] text-ink-60">{{ __("A curated collection of Egypt's most distinguished addresses.") }}</p>
            </div>
            <div data-grid-reveal class="{{ $reveal }} shrink-0 pb-1">
                <x-website.view-all :href="route('projects')">{{ __('View All') }}</x-website.view-all>
            </div>
        </header>

        <div x-ref="grid" class="grid auto-rows-[clamp(240px,21vw,360px)] grid-cols-3 gap-[14px] max-[900px]:grid-cols-2! max-[480px]:grid-cols-1!">
            @foreach (array_slice($projects, 0, 4) as $i => $project)
                <article
                    data-card
                    data-url="{{ $project['url'] }}"
                    @click="if (! $event.target.closest('[data-card-actions], [data-card-cta]')) window.location = $el.dataset.url"
                    class="{{ $areas[$i] }} group/card relative isolate cursor-pointer overflow-hidden rounded-xl bg-ink-10 transition-shadow duration-450 [clip-path:inset(0_100%_0_0_round_12px)] [transform:translateZ(0)] max-[900px]:col-auto! max-[900px]:row-auto!"
                >
                    <img data-img src="{{ $project['image'] }}" alt="{{ $project['title'] }}" loading="lazy" class="block size-full origin-center object-cover" />
                    <div data-dim class="pointer-events-none absolute inset-0 z-1 bg-black/18 opacity-0 will-change-[opacity]"></div>

                    <div data-panel class="absolute z-3 flex flex-col bg-white px-5 py-[22px] backdrop-blur-[18px] will-change-[clip-path,opacity] group-data-[type=landscape]/card:inset-y-0 group-data-[type=landscape]/card:left-0 group-data-[type=landscape]/card:w-[58%] group-data-[type=landscape]/card:justify-center group-data-[type=landscape]/card:rounded-r-xl group-data-[type=portrait]/card:top-auto group-data-[type=portrait]/card:bottom-0 group-data-[type=portrait]/card:w-full group-data-[type=portrait]/card:justify-end group-data-[type=portrait]/card:rounded-t-xl group-data-[type=square]/card:inset-[10px] group-data-[type=square]/card:justify-end group-data-[type=square]/card:rounded-[10px] dark:border dark:border-line dark:bg-[rgba(16,13,9,0.9)] dark:text-ink dark:backdrop-blur-[24px] dark:backdrop-saturate-140">
                        <span data-text class="{{ $text }} mb-[10px] inline-block w-fit rounded-[2px] border border-[rgba(200,16,46,0.5)] px-[10px] py-1 font-sans text-[9px] font-semibold tracking-[0.22em] text-brand uppercase rtl:font-arabic">{{ $project['badge'] }}</span>
                        <h3 data-text class="{{ $text }} mb-[5px] font-sans text-[clamp(14px,1.3vw,19px)] leading-[1.2] font-normal tracking-[-0.01em] text-ink rtl:font-arabic">{{ $project['title'] }}</h3>
                        @if ($project['location'])
                            <p data-text class="{{ $text }} mb-[10px] flex items-center gap-[5px] text-[11.5px] text-ink-60">
                                <svg viewBox="0 0 24 24" aria-hidden="true" class="size-[11px] shrink-0 fill-none stroke-brand stroke-[1.5] [stroke-linecap:round] [stroke-linejoin:round]">
                                    <path d="M12 21s-8-7.5-8-12a8 8 0 0116 0c0 4.5-8 12-8 12z" />
                                    <circle cx="12" cy="9" r="2.5" />
                                </svg>
                                {{ $project['location'] }}
                            </p>
                        @endif
                        @if ($project['price'])
                            <p data-text class="{{ $text }} mb-[14px] font-sans text-[clamp(14px,1.2vw,18px)] font-normal tracking-[-0.01em] text-brand rtl:font-arabic">{{ $project['price'] }}</p>
                        @endif
                        <a data-card-cta href="{{ $project['url'] }}" class="{{ $text }} group/cta mb-[14px] inline-flex w-fit items-center gap-[7px] pb-[3px] font-sans text-[10px] font-semibold tracking-[0.18em] text-ink uppercase transition-[border-color,color] duration-200 hover:border-brand hover:text-brand-dk rtl:font-arabic">
                            {{ __('Explore Project') }}
                            <x-website.icons.arrow class="h-3 w-[14px] transition-transform duration-250 group-hover/cta:translate-x-[3px] rtl:-scale-x-100 rtl:group-hover/cta:-translate-x-[3px]" />
                        </a>
                    </div>

                    <div data-card-actions class="pointer-events-none absolute top-[14px] right-[14px] z-10 flex translate-x-[10px] flex-col gap-[7px] opacity-0 [transition:opacity_0.32s_ease_0.06s,translate_0.42s_cubic-bezier(0.16,1,0.3,1)_0.06s] group-hover/card:pointer-events-auto group-hover/card:translate-x-0 group-hover/card:opacity-100">
                        <button
                            type="button"
                            x-data="{ id: 'project-{{ $project['id'] }}' }"
                            @click="$store.favourites.toggle(id); pop($el)"
                            class="{{ $action }} group-hover/card:delay-80"
                            :class="$store.favourites.has(id) ? 'border-transparent! bg-[rgba(220,38,38,0.88)]! text-white!' : 'border-white/18 bg-[rgba(8,8,8,0.52)] text-white/88'"
                            aria-label="{{ __('Save to favourites') }}"
                            title="{{ __('Save') }}"
                        >
                            <svg viewBox="0 0 24 24" class="{{ $actionIcon }}" :class="$store.favourites.has(id) && 'fill-current!'">
                                <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            @click="$store.projectLightbox.show(@js($project['images']), @js($project['title']))"
                            class="{{ $action }} border-white/18 bg-[rgba(8,8,8,0.52)] text-white/88 group-hover/card:delay-140"
                            aria-label="{{ __('View all photos') }}"
                            title="{{ __('Gallery') }}"
                        >
                            <svg viewBox="0 0 24 24" class="{{ $actionIcon }}">
                                <rect x="3" y="3" width="7" height="7" rx="1" />
                                <rect x="14" y="3" width="7" height="7" rx="1" />
                                <rect x="14" y="14" width="7" height="7" rx="1" />
                                <rect x="3" y="14" width="7" height="7" rx="1" />
                            </svg>
                        </button>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
