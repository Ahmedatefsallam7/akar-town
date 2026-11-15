{{--
    A listing page's search bar (the projects page's search box, alone in its sticky bar): the developers and locations
    pages (AKAR-47: a name, or a compound's name). Sends the Livewire property "q" as the visitor types.
--}}
@props(['placeholder'])

<div class="sticky top-nav z-100 border-b border-ink-10 bg-page dark:border-[rgba(237,232,223,0.07)] dark:bg-[rgba(13,11,8,0.95)]">
    <div class="mx-auto flex max-w-[1320px] items-center gap-2 px-12 py-[10px] max-xl:px-7 max-md:px-4 max-md:py-3">
        <div class="relative max-w-[320px] min-w-[160px] flex-1 max-md:max-w-full">
            <svg class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-ink-30 rtl:right-3 rtl:left-auto" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" /></svg>
            <input
                type="search"
                wire:model.live.debounce.350ms="q"
                placeholder="{{ $placeholder }}"
                aria-label="{{ $placeholder }}"
                class="w-full rounded-full border border-ink-10 bg-transparent py-[9px] leading-[normal] pr-[14px] pl-9 text-[13px] text-ink transition-[border-color] duration-200 outline-none placeholder:text-ink-30 focus:border-brand dark:border-line-md dark:bg-surface-2 dark:placeholder:text-ink-30 rtl:py-[10px] rtl:pr-[38px] rtl:pl-3 [&::-webkit-search-cancel-button]:hidden"
            />
        </div>
        <span wire:loading.delay wire:target="q" class="size-[14px] animate-spin rounded-full border-2 border-ink-10 border-t-brand" aria-hidden="true"></span>
    </div>
</div>
