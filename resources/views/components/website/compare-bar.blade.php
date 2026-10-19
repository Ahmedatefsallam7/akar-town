{{--
    Compare bar (the design's .cmp-bar): a pill at the bottom corner with the count, opening a panel with the chosen
    properties and "Compare Now". Hidden while the list is empty. State: Alpine store "compare".
--}}
<div
    x-data="{ shake(offset) { $el.style.transform = offset ? `translateX(${offset}px)` : '' } }"
    @compare-full.window="shake(6); setTimeout(() => shake(-6), 80); setTimeout(() => shake(0), 160)"
    class="pointer-events-none fixed right-8 bottom-8 z-900 flex translate-y-4 flex-col items-end gap-[10px] opacity-0 transition-[opacity,translate,transform] duration-250 rtl:right-auto rtl:left-8"
    :class="{ 'translate-y-4 opacity-0': ! $store.compare.items.length, 'translate-y-0 opacity-100': $store.compare.items.length }"
>
    <div
        class="pointer-events-none flex max-h-[420px] w-[300px] origin-bottom-right translate-y-[10px] scale-94 flex-col overflow-hidden rounded-[14px] border border-black/8 bg-[linear-gradient(160deg,#ffffff_0%,#f5f5f5_100%)] opacity-0 shadow-[0_8px_32px_rgba(0,0,0,0.12)] backdrop-blur-[24px] [transition:scale_0.32s_var(--ease-drawer),translate_0.32s_var(--ease-drawer),opacity_0.28s_ease] rtl:origin-bottom-left dark:border-line-md dark:bg-[linear-gradient(160deg,#1c1912_0%,#131008_100%)] dark:shadow-[0_0_0_1px_rgba(212,20,44,0.06),0_8px_40px_rgba(0,0,0,0.65),inset_0_0_60px_rgba(212,20,44,0.04)]"
        :class="{ 'pointer-events-none translate-y-[10px] scale-94 opacity-0': ! $store.compare.open, 'pointer-events-auto translate-y-0 scale-100 opacity-100': $store.compare.open }"
    >
        <div class="flex shrink-0 items-center justify-between gap-2 border-b border-black/7 bg-[rgba(200,16,46,0.05)] px-[14px] py-3 dark:border-line dark:bg-[rgba(212,20,44,0.07)]">
            <h4 class="flex-1 text-center text-[14px] font-semibold tracking-[0.02em] text-[#1a1a1a] dark:text-ink">{{ __('Property Comparison') }}</h4>
            <button
                type="button"
                @click="$store.compare.open = false"
                class="flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-full border border-black/12 bg-black/5 text-black/45 transition-[background,color,border-color] duration-200 hover:border-[rgba(200,16,46,0.3)] hover:bg-[rgba(200,16,46,0.12)] hover:text-[#c8102e] dark:border-line-md dark:bg-[rgba(237,232,223,0.05)] dark:text-ink-60 dark:hover:border-[rgba(212,20,44,0.28)] dark:hover:bg-[rgba(212,20,44,0.18)] dark:hover:text-ink"
                aria-label="{{ __('Close panel') }}"
            >
                <x-website.icons.close width="14" height="14" stroke-width="2.5" />
            </button>
        </div>

        <div class="flex flex-1 flex-col overflow-y-auto [&::-webkit-scrollbar]:w-1 [&::-webkit-scrollbar-thumb]:rounded [&::-webkit-scrollbar-thumb]:bg-black/15 dark:[&::-webkit-scrollbar-thumb]:bg-[rgba(237,232,223,0.12)] [&::-webkit-scrollbar-track]:bg-transparent">
            <template x-for="item in $store.compare.items" :key="item.id">
                <div class="flex items-center gap-[10px] border-b border-black/6 bg-transparent px-4 py-3 transition-[background] duration-200 last:border-b-0 hover:bg-black/3 dark:border-line dark:hover:bg-[rgba(237,232,223,0.04)]">
                    <img :src="item.thumb" :alt="item.title" class="size-12 shrink-0 rounded-lg border border-black/8 object-cover dark:border-line-md" />
                    <span class="min-w-0 flex-1 text-[13px] leading-[1.45] font-normal whitespace-normal text-black/75 dark:text-ink" x-text="item.title"></span>
                    <button
                        type="button"
                        @click="$store.compare.remove(item.id)"
                        class="shrink-0 cursor-pointer rounded-full border-none bg-none p-1 leading-[0] text-black/28 transition-[color,background] duration-200 hover:bg-[rgba(200,16,46,0.1)] hover:text-[#c8102e] dark:text-ink-30 dark:hover:bg-[rgba(212,20,44,0.15)] dark:hover:text-[#ff4466]"
                        aria-label="{{ __('Remove') }}"
                    >
                        <x-website.icons.close width="12" height="12" stroke-width="2.5" />
                    </button>
                </div>
            </template>
            <template x-for="slot in $store.compare.max - $store.compare.items.length" :key="'slot' + slot">
                <div class="border-b border-black/6 px-4 py-[14px] text-center text-[12px] tracking-[0.03em] text-black/35 last:border-b-0 dark:border-line dark:text-ink-30">{{ __('+ Add property') }}</div>
            </template>
        </div>

        <div class="shrink-0 border-t border-black/7 bg-transparent px-4 pt-[14px] pb-4 dark:border-line dark:bg-black/25">
            <a
                :href="@js(route('compare')) + '?units=' + $store.compare.items.map((item) => item.slug).filter(Boolean).join(',')"
                class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-full border border-white/12 bg-[linear-gradient(135deg,var(--color-brand)_0%,#a00d22_100%)] p-[13px] text-[13px] font-semibold tracking-[0.04em] text-white transition-all duration-300 ease-out-ex hover:-translate-y-0.5 hover:brightness-110"
            >{{ __('Compare Now') }}</a>
        </div>
    </div>

    <div class="pointer-events-auto relative z-1 flex flex-col">
        <button
            type="button"
            @click="$store.compare.open = ! $store.compare.open"
            class="group relative flex cursor-pointer flex-row items-center gap-[10px] overflow-hidden rounded-full border border-black/10 bg-[linear-gradient(135deg,#ffffff_0%,#f0f0f0_100%)] py-[10px] pr-[18px] pl-[14px] whitespace-nowrap text-[#1a1a1a] shadow-[0_4px_16px_rgba(0,0,0,0.1)] backdrop-blur-[20px] transition-all duration-350 ease-out-ex before:pointer-events-none before:absolute before:inset-0 before:rounded-[inherit] before:bg-[linear-gradient(135deg,rgba(200,16,46,0.08)_0%,transparent_55%)] hover:-translate-y-[3px] hover:border-[rgba(200,16,46,0.35)] dark:border-line-md dark:bg-[linear-gradient(135deg,#1c1912_0%,#131008_100%)] dark:text-ink dark:shadow-[0_4px_20px_rgba(0,0,0,0.5),0_0_0_1px_rgba(212,20,44,0.06)] dark:before:bg-[linear-gradient(135deg,rgba(212,20,44,0.14)_0%,transparent_55%)] dark:hover:border-[rgba(212,20,44,0.28)] dark:hover:shadow-[0_6px_28px_rgba(0,0,0,0.6),0_0_0_1px_rgba(212,20,44,0.14)]"
            aria-label="{{ __('Toggle compare panel') }}"
        >
            <x-website.icons.compare width="18" height="18" />
            <span class="text-[13px] font-semibold tracking-[0.02em] text-black/80 dark:text-ink">{{ __('Compare') }}</span>
            <span class="rounded-full bg-brand px-[9px] py-[3px] text-[11px] leading-[1.4] font-bold tracking-[0.02em] text-white" x-text="$store.compare.items.length + '/' + $store.compare.max"></span>
            <svg class="text-black/60 opacity-50 transition-[rotate,opacity] duration-300 ease-drawer group-hover:opacity-90 dark:text-ink-60" :class="$store.compare.open && 'rotate-180'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="18 15 12 9 6 15" />
            </svg>
        </button>
    </div>
</div>
