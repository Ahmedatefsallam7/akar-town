{{--
    Projects listing (the design's projects.html above the CTA banner): header with the number of matching projects,
    sticky filter bar (filters apply at once, as in the design), the cards and "Load More Projects".
--}}
@php
    $pad = 'px-12 max-xl:px-7 max-md:px-5';
    $clear = 'rounded-full border leading-[normal] border-transparent px-[14px] py-[7px] text-[11px] font-medium tracking-[0.1em] text-ink-30 uppercase transition-[color,border-color] duration-200 hover:border-[rgba(200,16,46,0.2)] hover:text-danger dark:text-ink-60 dark:hover:border-[rgba(212,20,44,0.25)] dark:hover:text-brand rtl:font-arabic';
    $apply = 'inline-flex cursor-pointer leading-[normal] items-center gap-2 rounded-full border border-white/20 bg-[linear-gradient(135deg,rgba(215,20,52,0.96)_0%,rgb(185,10,36)_100%)] px-5 py-[9px] text-[12px] font-semibold tracking-[0.04em] text-white transition-all duration-300 ease-out-ex hover:-translate-y-0.5 hover:bg-[linear-gradient(135deg,rgba(225,25,58,0.98)_0%,rgb(200,16,46)_100%)] rtl:font-arabic';
    $sorts = [
        'featured' => __('Featured First'),
        'newest' => __('Newest'),
        'price-asc' => __('Price: Low to High'),
        'price-desc' => __('Price: High to Low'),
        'completion' => __('Completion Date'),
    ];
@endphp

<div>
    <x-website.page-hero :crumb="__('Projects')" :title="__('Explore <em>Exceptional</em> Projects')" :count="$total" :count-label="__('Active Projects')" :animate="false" />

    {{-- Filter bar --}}
    <div class="sticky top-nav z-100 border-b border-ink-10 bg-page dark:border-[rgba(237,232,223,0.07)] dark:bg-[rgba(13,11,8,0.95)]">
        <div class="{{ $pad }} mx-auto flex max-w-[1320px] flex-wrap items-center gap-2 py-[10px] max-md:px-4 max-md:py-3">
            <div class="relative min-w-[160px] flex-1 max-w-[240px] max-md:max-w-full max-md:basis-full">
                <svg class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-ink-30 rtl:right-3 rtl:left-auto" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" /></svg>
                <input
                    type="text"
                    wire:model.live.debounce.350ms="q"
                    placeholder="{{ __('Search projects…') }}"
                    aria-label="{{ __('Search projects…') }}"
                    class="w-full rounded-full border border-ink-10 bg-transparent py-[9px] leading-[normal] pr-[14px] pl-9 text-[13px] text-ink transition-[border-color] duration-200 outline-none placeholder:text-ink-30 focus:border-brand dark:border-line-md dark:bg-surface-2 dark:placeholder:text-ink-30 rtl:py-[10px] rtl:pr-[38px] rtl:pl-3"
                />
            </div>

            <x-website.filter-select model="location" live :label="__('Location')" :placeholder="__('All Locations')" :options="$locations">
                <x-slot:icon><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" /><circle cx="12" cy="10" r="3" /></svg></x-slot:icon>
            </x-website.filter-select>
            <x-website.filter-select model="type" live :label="__('Type')" :placeholder="__('All Types')" :options="$types">
                <x-slot:icon><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" /><polyline points="9 22 9 12 15 12 15 22" /></svg></x-slot:icon>
            </x-website.filter-select>
            <x-website.filter-select model="status" live :label="__('Status')" :placeholder="__('All Status')" :options="$statuses">
                <x-slot:icon><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" /></svg></x-slot:icon>
            </x-website.filter-select>
            <x-website.filter-select model="developer" live :label="__('Developer')" :placeholder="__('All Developers')" :options="$developers">
                <x-slot:icon><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" /><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2" /></svg></x-slot:icon>
            </x-website.filter-select>

            <div class="mx-1 h-7 w-px shrink-0 bg-ink-10"></div>

            <x-website.filter-select model="sort" live :label="__('Sort')" :placeholder="__('Featured First')" :options="collect($sorts)->except('featured')->all()">
                <x-slot:icon><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="4" y1="6" x2="20" y2="6" /><line x1="8" y1="12" x2="16" y2="12" /><line x1="11" y1="18" x2="13" y2="18" /></svg></x-slot:icon>
            </x-website.filter-select>

            <div class="ml-auto flex shrink-0 items-center gap-2 rtl:mr-auto rtl:ml-0">
                <button type="button" wire:click="clear" class="{{ $clear }}">{{ __('Reset') }}</button>
                <button type="button" wire:click="$refresh" class="{{ $apply }}">{{ __('Apply') }}</button>
            </div>
        </div>
    </div>

    {{-- Cards --}}
    <main class="{{ $pad }} mx-auto max-w-[1320px] pt-9 pb-20 max-md:pt-6 max-md:pb-[60px]" aria-label="{{ __('Project listings') }}">
        <div class="grid grid-cols-3 gap-7 max-xl:grid-cols-2 max-md:grid-cols-1">
            @forelse ($cards as $i => $card)
                <x-website.project-card :card="$card" :index="$i" wire:key="project-{{ $card['id'] }}" />
            @empty
                <div class="col-span-full flex flex-col items-center gap-4 px-5 py-20 text-center">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-ink-10" aria-hidden="true"><circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" /></svg>
                    <h3 class="text-[1.1rem] font-semibold text-ink-60 rtl:font-arabic">{{ __('No projects found') }}</h3>
                    <p class="max-w-[30ch] text-[13px] text-ink-30">{{ __('Try adjusting your filters to see more results.') }}</p>
                </div>
            @endforelse
        </div>
    </main>

    @if ($more)
        <x-website.load-more>{{ __('Load More Projects') }}</x-website.load-more>
    @endif
</div>
