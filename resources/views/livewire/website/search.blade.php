{{--
    The search page (App\Livewire\Website\Search, AKAR-48): the header, the search bar (the text in the URL), then
    every result grouped by type as cards (photo or icon, the typed text marked, the type, where it is, a price or
    count), each group linking to its full listing; close matches when a typo found nothing.
--}}
@php
    $groups = [
        'compounds' => ['title' => __('Compounds'), 'type' => __('Compound'), 'all' => null],
        'properties' => ['title' => __('Properties'), 'type' => __('Property'), 'all' => 'properties'],
        'projects' => ['title' => __('Projects'), 'type' => __('Project'), 'all' => 'projects'],
        'developers' => ['title' => __('Developers'), 'type' => __('Developer'), 'all' => 'developers'],
        'locations' => ['title' => __('Locations'), 'type' => __('Location'), 'all' => 'locations'],
    ];
    $icons = [
        'compounds' => '<rect x="3" y="3" width="7" height="9" rx="1" /><rect x="14" y="3" width="7" height="5" rx="1" /><rect x="14" y="12" width="7" height="9" rx="1" /><rect x="3" y="16" width="7" height="5" rx="1" />',
        'properties' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" /><polyline points="9 22 9 12 15 12 15 22" />',
        'projects' => '<path d="M3 21h18" /><path d="M5 21V7l8-4v18" /><path d="M19 21V11l-6-4" /><path d="M9 9v.01M9 12v.01M9 15v.01M9 18v.01" />',
        'developers' => '<rect x="2" y="7" width="20" height="14" rx="2" /><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2" />',
        'locations' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" /><circle cx="12" cy="10" r="3" />',
    ];
    $icon = fn (string $type, string $class = 'size-4') => '<svg class="'.$class.'" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$type].'</svg>';
    // The typed text marked in a name (same letters, any case), the rest escaped.
    $mark = function (string $title) use ($term): string {
        $at = $term !== '' ? mb_stripos($title, $term) : false;
        if ($at === false) {
            return e($title);
        }

        return e(mb_substr($title, 0, $at)).'<mark class="rounded-[3px] bg-[rgba(200,16,46,0.12)] px-px text-brand dark:bg-[rgba(200,16,46,0.25)] dark:text-[#ff8a9b]">'.e(mb_substr($title, $at, mb_strlen($term))).'</mark>'.e(mb_substr($title, $at + mb_strlen($term)));
    };
@endphp

<div>
    <x-website.page-hero :crumb="__('Search')" :title="__('Search <em>Results</em>')" :count="$total" :count-label="__('Results')" :animate="false" />
    <x-website.listing-search :placeholder="__('Search a compound, property, project, developer or location…')" />

    <div class="mx-auto max-w-[1320px] px-12 pt-10 pb-16 max-[1100px]:px-7 max-md:px-5 max-md:pt-6 max-md:pb-12" wire:loading.class="opacity-60" wire:target="q">
        @if (mb_strlen($term) < 2)
            <div class="px-6 py-20 text-center" role="status">
                <p class="mb-2 font-sans text-[1.6rem] font-light text-ink rtl:font-arabic">{{ __('What are you looking for?') }}</p>
                <p class="text-[13px] text-ink-30 rtl:font-arabic">{{ __('Type at least two letters, e.g. a compound’s name.') }}</p>
            </div>
        @elseif ($total === 0)
            <div class="flex flex-col items-center gap-3 px-6 py-20 text-center" role="status">
                <span class="grid size-16 place-items-center rounded-full bg-[linear-gradient(135deg,rgba(200,16,46,0.10),rgba(200,16,46,0.02))] text-brand">
                    <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" /><path d="M8 11h6" /></svg>
                </span>
                <p class="font-sans text-[1.6rem] font-light text-ink rtl:font-arabic">{{ __('No results for “:q”', ['q' => $term]) }}</p>
                <p class="text-[13px] text-ink-30 rtl:font-arabic">{{ __('Try a compound, area, project or developer name.') }}</p>
            </div>
        @else
            @if ($near)
                <p class="mb-8 flex items-center gap-2.5 rounded-[14px] border border-[rgba(200,16,46,0.15)] bg-[rgba(200,16,46,0.04)] px-4 py-3 text-[13.5px] text-ink-60 rtl:font-arabic dark:text-white/65" role="status">
                    <svg class="size-4 shrink-0 text-brand" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" /></svg>
                    {{ __('No exact match for “:q”, showing close matches.', ['q' => $term]) }}
                </p>
            @endif

            <div class="flex flex-col gap-12 max-md:gap-9">
                @foreach ($groups as $key => $group)
                    @if ($results[$key])
                        <section wire:key="search-{{ $key }}" aria-labelledby="search-{{ $key }}">
                            <div class="mb-4 flex items-center gap-3">
                                <span class="grid size-9 shrink-0 place-items-center rounded-[10px] bg-[rgba(200,16,46,0.08)] text-brand">{!! $icon($key, 'size-[17px]') !!}</span>
                                <h2 id="search-{{ $key }}" class="font-sans text-[1.35rem] font-light text-ink rtl:font-arabic dark:text-[#ede8df]">
                                    {{ $group['title'] }}
                                    <span class="ms-1 align-middle text-[12px] font-semibold text-ink-30">{{ count($results[$key]) }}</span>
                                </h2>
                                <span class="h-px flex-1 bg-ink-10 dark:bg-white/8"></span>
                                @if ($group['all'])
                                    <a href="{{ route($group['all'], ['q' => $term]) }}" class="inline-flex shrink-0 items-center gap-1.5 text-[12.5px] font-semibold text-brand transition-[gap] duration-150 hover:gap-2.5 rtl:font-arabic">
                                        {{ __('See all') }}
                                        <svg class="size-3.5 rtl:-scale-x-100" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14" /><path d="m12 5 7 7-7 7" /></svg>
                                    </a>
                                @endif
                            </div>

                            <div class="grid grid-cols-3 gap-3 max-[1100px]:grid-cols-2 max-md:grid-cols-1">
                                @foreach ($results[$key] as $item)
                                    <a href="{{ $item['url'] }}" wire:key="search-{{ $key }}-{{ $item['url'] }}" class="group flex items-center gap-3.5 rounded-[16px] border border-ink-10 bg-white p-2.5 pe-4 transition-[border-color,box-shadow,translate] duration-200 hover:-translate-y-0.5 hover:border-[rgba(200,16,46,0.3)] hover:shadow-[0_14px_30px_-16px_rgba(0,0,0,0.25)] motion-safe:animate-fade-up dark:border-white/8 dark:bg-white/3">
                                        @if ($item['image'])
                                            <img src="{{ $item['image'] }}" alt="" width="72" height="72" loading="lazy" decoding="async" class="size-[72px] shrink-0 rounded-[12px] bg-black/5 object-cover dark:bg-white/5" />
                                        @else
                                            <span class="grid size-[72px] shrink-0 place-items-center rounded-[12px] bg-[linear-gradient(135deg,rgba(200,16,46,0.14),rgba(200,16,46,0.04))] text-brand">{!! $icon($key, 'size-6') !!}</span>
                                        @endif
                                        <span class="flex min-w-0 flex-1 flex-col gap-1.5">
                                            <span class="truncate text-[15px] leading-tight font-semibold text-ink transition-colors duration-150 group-hover:text-brand rtl:font-arabic dark:text-[#ede8df]">{!! $mark($item['title']) !!}</span>
                                            <span class="flex min-w-0 items-center gap-1.5 text-[12px] text-ink-30 rtl:font-arabic">
                                                <span class="shrink-0 rounded-[5px] border border-black/8 px-1.5 py-px text-[10px] font-semibold tracking-[0.04em] text-ink-60 uppercase rtl:tracking-normal dark:border-white/10 dark:text-white/55">{{ $group['type'] }}</span>
                                                @if ($item['sub'])
                                                    <span class="truncate">{{ $item['sub'] }}</span>
                                                @endif
                                            </span>
                                            @if ($item['badge'] ?? null)
                                                <span @class([
                                                    'self-start rounded-full px-2.5 py-0.5 text-[11.5px] font-semibold whitespace-nowrap',
                                                    'bg-[rgba(200,16,46,0.08)] font-sans text-brand' => $key === 'properties',
                                                    'bg-black/5 text-ink-60 rtl:font-arabic dark:bg-white/6 dark:text-white/60' => $key !== 'properties',
                                                ])>{{ $item['badge'] }}</span>
                                            @endif
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>
