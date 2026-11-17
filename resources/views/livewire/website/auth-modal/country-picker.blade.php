{{-- The country picker (see openDial): on the page itself, above the window, never cut off. --}}
@teleport('body')
    <div x-show="open && dial" x-cloak
        x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="-translate-y-1 opacity-0"
        x-transition:leave="transition duration-100 ease-in" x-transition:leave-end="opacity-0"
        :style="pos"
        @click.outside="dial && ! $refs.field.contains($event.target) && closeDial(false)"
        @resize.window="dial && place()"
        class="fixed z-[10000] flex flex-col overflow-hidden rounded-[14px] border border-[#e8e5e0] bg-white shadow-[0_18px_44px_-14px_rgba(0,0,0,0.35)] dark:border-white/10 dark:bg-[#1c1c1c]"
        dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
        <div class="flex flex-none items-center gap-2 border-b border-black/7 px-3 dark:border-white/8">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4 flex-none text-black/35 dark:text-white/35" aria-hidden="true"><circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" /></svg>
            <input x-ref="find" x-model="find" type="search" autocomplete="off" placeholder="{{ __('Search a country or code') }}" aria-label="{{ __('Search a country or code') }}"
                @input="active = 0"
                @keydown.down.prevent="step(1)" @keydown.up.prevent="step(-1)"
                @keydown.enter.prevent="found()[active] && pick(found()[active])"
                @keydown.escape.stop.prevent="closeDial()" @keydown.tab="closeDial(false)"
                class="h-11 min-w-0 flex-1 bg-transparent text-[13.5px] text-[#0d0d0d] outline-none placeholder:text-black/35 rtl:font-arabic dark:text-[#f0ede8] dark:placeholder:text-white/30 [&::-webkit-search-cancel-button]:hidden" />
        </div>
        <div x-ref="options" class="min-h-0 flex-1 overflow-y-auto py-1" role="listbox" aria-label="{{ __('Choose your country') }}">
            <template x-for="(item, i) in found()" :key="item[0]">
                <button type="button" role="option" :aria-selected="country === item[4]"
                    @click="pick(item)" @mouseenter="active = i"
                    class="flex w-full cursor-pointer items-center gap-3 px-3.5 py-2.5 text-start transition-[background] duration-100"
                    :class="active === i ? 'bg-[rgba(200,16,46,0.06)] dark:bg-[rgba(200,16,46,0.12)]' : ''">
                    <span class="text-[20px] leading-none" x-text="item[1]"></span>
                    <span class="min-w-0 flex-1 truncate text-[13.5px] text-[#0d0d0d] rtl:font-arabic dark:text-[#f0ede8]" :class="country === item[4] && 'font-semibold'" x-text="item[3]"></span>
                    <bdi dir="ltr" class="text-[12.5px] text-black/45 tabular-nums dark:text-white/40" x-text="item[2]"></bdi>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="size-4 flex-none text-brand" :class="country === item[4] ? 'opacity-100' : 'opacity-0'" aria-hidden="true"><polyline points="20 6 9 17 4 12" /></svg>
                </button>
            </template>
            <p x-show="! found().length" class="px-3.5 py-4 text-center text-[13px] text-black/45 rtl:font-arabic dark:text-white/40">{{ __('No country found') }}</p>
        </div>
    </div>
@endteleport
