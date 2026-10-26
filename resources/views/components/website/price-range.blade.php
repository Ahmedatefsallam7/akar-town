{{--
    The listings' price filter as the design's range slider (listings.html #priceFilter): a pill like the other filters
    opening two handles from 0 to 50M+ EGP in 1M steps. It sets the Livewire array property to one "from-to" range in
    EGP ("5000000-20000000", "50000000-" for 50M+; empty for any price) without a request, sent with "Apply" as the
    other filters; "Reset" (the property cleared) puts the handles back.
--}}
@props(['model', 'label', 'placeholder'])

@php($max = 50)

<div
    x-data="{
        open: false,
        max: {{ $max }},
        lo: 0,
        hi: {{ $max }},
        init() {
            this.read()
            $wire.$watch(@js($model), () => this.read())
        },
        read() {
            const match = String(($wire.get(@js($model)) ?? [])[0] ?? '').match(/^(\d+)-(\d*)$/)
            this.lo = match ? Math.min(Math.round(match[1] / 1e6), this.max - 1) : 0
            this.hi = match && match[2] !== '' ? Math.min(Math.round(match[2] / 1e6), this.max) : this.max
        },
        get any() {
            return this.lo === 0 && this.hi >= this.max
        },
        amount(n) {
            return (n >= this.max ? @js(__(':countM+ EGP')) : @js(__(':countM EGP'))).replace(':count', n)
        },
        get text() {
            return this.any ? @js($placeholder) : this.amount(this.lo) + ' – ' + this.amount(this.hi)
        },
        move(handle) {
            this.lo = Number(this.lo)
            this.hi = Number(this.hi)
            if (this.lo >= this.hi) {
                handle === 'lo' ? (this.lo = this.hi - 1) : (this.hi = this.lo + 1)
            }
            const range = this.lo * 1e6 + '-' + (this.hi >= this.max ? '' : this.hi * 1e6)
            $wire.set(@js($model), this.any ? [] : [range], false)
        },
    }"
    @click.outside="open = false"
    class="relative shrink-0"
>
    <button
        type="button"
        @click="open = ! open"
        aria-haspopup="dialog"
        :aria-expanded="open"
        class="flex cursor-pointer items-center gap-2 rounded-full border bg-transparent py-1.5 pr-[10px] pl-[7px] whitespace-nowrap outline-none transition-[border-color,background] duration-220 hover:border-[rgba(200,16,46,0.4)] hover:bg-[rgba(200,16,46,0.04)] dark:border-line-md dark:bg-surface-2"
        :class="open ? 'border-brand bg-[rgba(200,16,46,0.06)]' : 'border-ink-10'"
    >
        @isset($icon)
            <span class="grid size-7 shrink-0 place-items-center rounded-full bg-[rgba(200,16,46,0.08)] text-brand">{{ $icon }}</span>
        @endisset
        <span class="flex min-w-0 flex-col items-start leading-[1.2]">
            <span class="text-[9px] font-semibold tracking-[0.14em] text-ink-30 uppercase rtl:font-arabic">{{ $label }}</span>
            <span class="text-[12px] font-semibold text-ink rtl:text-right rtl:font-arabic" x-text="text">{{ $placeholder }}</span>
        </span>
        <svg class="shrink-0 transition-[rotate,color] duration-280 ease-smooth" :class="open ? 'rotate-180 text-brand' : 'text-ink-30'" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><polyline points="6,9 12,15 18,9" /></svg>
    </button>

    <div
        role="dialog"
        aria-label="{{ $label }}"
        class="pointer-events-none absolute top-[calc(100%+6px)] left-0 z-150 w-[284px] origin-top -translate-y-1.5 scale-y-96 rounded-xl border border-ink-10 bg-page px-[18px] pt-4 pb-[14px] opacity-0 shadow-[0_4px_6px_-1px_rgba(13,13,13,0.06),0_16px_40px_-4px_rgba(13,13,13,0.12)] [transition:opacity_0.18s_var(--ease-out-ex),translate_0.18s_var(--ease-out-ex),scale_0.18s_var(--ease-out-ex)] dark:border-line-md dark:bg-surface-2 rtl:right-0 rtl:left-auto"
        :class="{ 'pointer-events-auto translate-y-0 scale-y-100 opacity-100': open, 'pointer-events-none -translate-y-1.5 scale-y-96 opacity-0': ! open }"
    >
        <div class="mb-4 text-center text-[13px] font-semibold text-ink rtl:font-arabic" x-text="any ? @js(__('Any price')) : text">{{ __('Any price') }}</div>

        {{-- Two range inputs on one track: only their handles take the pointer. Left to right in both languages. --}}
        @php($handle = 'pointer-events-none absolute top-1/2 m-0 h-1 w-full -translate-y-1/2 appearance-none bg-transparent [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:size-[18px] [&::-moz-range-thumb]:cursor-pointer [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-brand [&::-moz-range-thumb]:bg-white [&::-moz-range-thumb]:shadow-[0_1px_4px_rgba(0,0,0,0.14)] [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:size-[18px] [&::-webkit-slider-thumb]:cursor-pointer [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-brand [&::-webkit-slider-thumb]:bg-white [&::-webkit-slider-thumb]:shadow-[0_1px_4px_rgba(0,0,0,0.14)]')
        <div dir="ltr" class="relative mt-[14px] mb-[10px] h-1 rounded-[2px] bg-ink-10">
            <div class="pointer-events-none absolute h-full rounded-[2px] bg-brand" :style="`left: ${lo / max * 100}%; width: ${(hi - lo) / max * 100}%`"></div>
            <input type="range" min="0" max="{{ $max }}" step="1" x-model.number="lo" @input="move('lo')" class="{{ $handle }}" aria-label="{{ __('Minimum price') }}" />
            <input type="range" min="0" max="{{ $max }}" step="1" x-model.number="hi" @input="move('hi')" class="{{ $handle }}" aria-label="{{ __('Maximum price') }}" />
        </div>
        <div dir="ltr" class="mt-2 flex justify-between text-[10px] text-ink-30">
            @foreach ([0, 10, 20, 30, 40, 50] as $tick)
                <span>{{ $tick ? $tick.__('M').($tick === $max ? '+' : '') : '0' }}</span>
            @endforeach
        </div>
    </div>
</div>
