{{--
    The Privacy Policy / Terms of Use page (not in the design; Dastan's layout in the site's look): the listing header
    (title, number of sections), a table of contents that follows the reading (not on phones), then the introduction and the
    numbered sections from their Settings page, and a "Questions?" box to the contact page. The texts are plain text from
    the admin: a blank line starts a new paragraph.
--}}
@props(['title', 'description', 'entries', 'question'])

@php
    $locale = app()->getLocale();
    $paragraphs = fn (?string $text) => array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/u', (string) $text))));
    $sections = collect($entries)
        ->filter(fn (array $entry) => filled($entry["header_{$locale}"] ?? null))
        ->values()
        ->map(fn (array $entry, int $i) => ['id' => 'section-'.($i + 1), 'title' => $entry["header_{$locale}"], 'body' => $paragraphs($entry["body_{$locale}"] ?? '')])
        ->all();
    $reveal = 'opacity-0 [transform:translateY(24px)] transition-[opacity,transform] duration-700 ease-out-ex [&.is-in]:opacity-100 [&.is-in]:[transform:none]';
@endphp

<x-website.page-hero
    :crumb="$title"
    :title="e($title)"
    :count="count($sections)"
    :count-label="trans_choice('Section|Sections', count($sections))"
    :animate="false"
/>

<section
    x-data="{ active: @js($sections[0]['id'] ?? null) }"
    class="bg-page pt-14 pb-[100px] max-[900px]:pt-8 max-[900px]:pb-[60px] dark:bg-[#100f0d]"
>
    <div class="mx-auto grid max-w-[1320px] grid-cols-[260px_1fr] items-start gap-12 px-12 max-xl:px-7 max-[900px]:grid-cols-1 max-[900px]:gap-7 max-md:px-5">
        {{-- Table of contents: the section being read is marked --}}
        @if ($sections)
            <nav class="sticky top-[calc(var(--spacing-nav)+24px)] self-start rounded-2xl border border-ink-10 bg-white p-5 max-[900px]:hidden dark:border-white/8 dark:bg-[#1a1916]" aria-label="{{ __('On this page') }}">
                <p class="mb-3 text-[10px] font-bold tracking-[0.2em] text-ink-30 uppercase rtl:font-arabic rtl:tracking-normal dark:text-white/40">{{ __('On this page') }}</p>
                <ol class="flex flex-col gap-1">
                    @foreach ($sections as $i => $section)
                        <li>
                            <a
                                href="#{{ $section['id'] }}"
                                class="flex items-start gap-2.5 rounded-lg px-2.5 py-2 text-[13px] leading-[1.45] transition-[background,color] duration-200 rtl:font-arabic"
                                :class="active === @js($section['id']) ? 'bg-[rgba(200,16,46,0.07)] font-semibold text-brand' : 'text-ink-60 hover:text-ink'"
                            >
                                <span class="mt-px shrink-0 font-sans text-[11px] font-bold opacity-60">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <span>{{ $section['title'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        <article x-intersect.once.threshold.05="$el.classList.add('is-in')" class="{{ $reveal }} min-w-0 rounded-2xl border border-ink-10 bg-white p-12 shadow-[0_8px_32px_rgba(13,13,13,0.05)] max-xl:p-9 max-md:p-6 dark:border-white/8 dark:bg-[#1a1916] {{ $sections ? '' : 'col-span-full' }}">
            @if ($intro = $paragraphs($description))
                <div class="mb-2 border-b border-ink-10 pb-7 dark:border-white/8">
                    @foreach ($intro as $paragraph)
                        <p class="text-[17px] leading-[1.8] text-ink-60 rtl:font-arabic [&:not(:last-child)]:mb-4">{!! nl2br(e($paragraph)) !!}</p>
                    @endforeach
                </div>
            @endif

            @foreach ($sections as $i => $section)
                <div
                    id="{{ $section['id'] }}"
                    x-intersect:enter.margin.-35%.0px.-55%.0px="active = @js($section['id'])"
                    class="scroll-mt-[calc(var(--spacing-nav)+24px)] border-b border-ink-10 py-8 last:border-b-0 last:pb-0 dark:border-white/8"
                >
                    <h2 class="mb-4 flex items-baseline gap-3 font-sans text-[22px] leading-[1.3] font-normal tracking-[-0.01em] text-ink rtl:font-arabic rtl:tracking-normal">
                        <span class="shrink-0 text-[13px] font-bold text-brand">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <span>{{ $section['title'] }}</span>
                    </h2>
                    @foreach ($section['body'] as $paragraph)
                        <p class="text-[15px] leading-[1.85] text-ink-60 rtl:font-arabic [&:not(:last-child)]:mb-4">{!! nl2br(e($paragraph)) !!}</p>
                    @endforeach
                </div>
            @endforeach

            {{-- Questions? --}}
            <div class="mt-10 flex items-center justify-between gap-6 rounded-2xl bg-[linear-gradient(135deg,rgb(13,11,8)_0%,rgb(22,12,13)_100%)] px-8 py-7 max-md:flex-col max-md:items-start max-md:px-6">
                <div>
                    <p class="mb-1 text-[17px] font-semibold text-white rtl:font-arabic">{{ $question }}</p>
                    <p class="text-[13px] text-white/55 rtl:font-arabic">{{ __('Reach out to our team and we’ll answer as soon as we can.') }}</p>
                </div>
                <x-website.button :href="route('contact')" arrow class="shrink-0">{{ __('Contact Us') }}</x-website.button>
            </div>
        </article>
    </div>
</section>
