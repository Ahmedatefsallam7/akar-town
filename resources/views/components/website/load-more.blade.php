{{--
    The listings' "Load More" glass pill (the design's .btn-load-more): calls the Livewire component's loadMore(),
    with a spinner while it loads. Used on the properties, projects, developers and locations listings.
--}}
{{-- 28 px above (the cards' gap; the list adds no bottom space while it shows), 48 px below (the design). --}}
<div class="flex justify-center pt-7 pb-12">
    <button
        type="button"
        wire:click="loadMore"
        wire:loading.attr="data-loading"
        wire:target="loadMore"
        class="group/more flex cursor-pointer items-center gap-[10px] rounded-full border-[1.5px] border-white/65 bg-white/48 px-9 py-[14px] text-[12px] leading-[normal] font-medium tracking-[0.04em] text-brand-dk shadow-[0_2px_16px_rgba(0,0,0,0.08),0_1px_4px_rgba(0,0,0,0.05)] backdrop-blur-[20px] backdrop-saturate-180 transition-all duration-300 ease-out-ex hover:-translate-y-0.5 hover:border-[rgba(200,16,46,0.35)] hover:bg-white/65 hover:shadow-[0_6px_24px_rgba(0,0,0,0.12),0_2px_6px_rgba(0,0,0,0.07)] dark:border-[rgba(237,232,223,0.13)] dark:bg-[rgba(28,25,18,0.8)] dark:text-ink dark:shadow-none dark:backdrop-blur-none dark:hover:border-[rgba(200,16,46,0.35)] dark:hover:bg-[rgba(40,35,24,0.9)] dark:hover:text-brand rtl:font-arabic"
    >
        <span class="hidden size-[14px] animate-spin rounded-full border-2 border-ink-10 border-t-brand [animation-duration:0.7s] group-data-loading/more:block"></span>
        <span class="group-data-loading/more:opacity-50">{{ $slot }}</span>
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19" /><polyline points="19 12 12 19 5 12" /></svg>
    </button>
</div>
