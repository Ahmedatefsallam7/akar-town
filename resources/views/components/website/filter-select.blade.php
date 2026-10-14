{{--
    The listings' custom select (the design's .csel): a pill with an icon, a small label and the chosen value, opening
    a menu. Filters are set without a request (sent with "Apply", as in the design); "live" ones (sort) apply at once.
    The Livewire property may be an array (filters: one chosen value) or a string (sort).
--}}
@props(['model', 'options', 'placeholder', 'label' => null, 'live' => false, 'variant' => 'filter'])

@php
    $options = ['' => $placeholder] + $options;
@endphp

<div
    x-data="{
        open: false,
        options: @js(array_map('strval', $options)),
        get current() {
            const value = $wire.get(@js($model))
            return String(Array.isArray(value) ? (value[0] ?? '') : (value ?? ''))
        },
        choose(value) {
            const array = Array.isArray($wire.get(@js($model)))
            $wire.set(@js($model), array ? (value === '' ? [] : [value]) : value, @js($live))
            this.open = false
        },
    }"
    @click.outside="open = false"
    class="relative shrink-0"
>
    <button
        type="button"
        @click="open = ! open"
        aria-haspopup="listbox"
        :aria-expanded="open"
        @class([
            'flex cursor-pointer items-center gap-2 rounded-full border bg-transparent whitespace-nowrap outline-none transition-[border-color,background] duration-220 hover:border-[rgba(200,16,46,0.4)] hover:bg-[rgba(200,16,46,0.04)] dark:border-line-md dark:bg-surface-2',
            'py-1.5 pr-[10px] pl-[7px]' => $variant === 'filter',
            'min-w-[150px] py-[7px] pr-[10px] pl-[13px] text-[12px] max-[700px]:min-w-[100px]' => $variant === 'sort',
        ])
        :class="open ? 'border-brand bg-[rgba(200,16,46,0.06)]' : 'border-ink-10'"
    >
        @isset($icon)
            <span class="grid size-7 shrink-0 place-items-center rounded-full bg-[rgba(200,16,46,0.08)] text-brand">{{ $icon }}</span>
        @endisset
        <span class="flex min-w-0 flex-col items-start leading-[1.2]">
            @if ($label)
                <span class="text-[9px] font-semibold tracking-[0.14em] text-ink-30 uppercase rtl:font-arabic">{{ $label }}</span>
            @endif
            <span class="text-[12px] font-semibold text-ink transition-colors duration-200 rtl:text-right" x-text="options[current] ?? options['']">{{ $placeholder }}</span>
        </span>
        <svg class="shrink-0 transition-[rotate,color] duration-280 ease-smooth" :class="open ? 'rotate-180 text-brand' : 'text-ink-30'" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><polyline points="6,9 12,15 18,9" /></svg>
    </button>

    <div
        role="listbox"
        class="pointer-events-none absolute top-[calc(100%+6px)] left-0 z-150 max-h-[280px] min-w-full origin-top -translate-y-1.5 scale-y-96 overflow-y-auto overscroll-contain rounded-xl border border-ink-10 bg-page opacity-0 shadow-[0_4px_6px_-1px_rgba(13,13,13,0.06),0_16px_40px_-4px_rgba(13,13,13,0.12)] [transition:opacity_0.18s_var(--ease-out-ex),translate_0.18s_var(--ease-out-ex),scale_0.18s_var(--ease-out-ex)] dark:border-line-md dark:bg-surface-2 dark:shadow-[0_4px_6px_-1px_rgba(0,0,0,0.4),0_20px_48px_-4px_rgba(0,0,0,0.7)] rtl:right-0 rtl:left-auto"
        :class="{ 'pointer-events-auto translate-y-0 scale-y-100 opacity-100': open, 'pointer-events-none -translate-y-1.5 scale-y-96 opacity-0': ! open }"
    >
        @foreach ($options as $value => $optionLabel)
            <div
                role="option"
                @click="choose(@js((string) $value))"
                :aria-selected="current === @js((string) $value)"
                class="cursor-pointer px-[14px] py-[9px] text-[13px] whitespace-nowrap transition-[background,color] duration-120 rtl:font-arabic"
                :class="current === @js((string) $value) ? 'bg-[rgba(200,16,46,0.08)] font-medium text-brand-dk' : 'text-ink-60 hover:bg-[rgba(200,16,46,0.06)] hover:text-ink'"
            >{{ $optionLabel }}</div>
        @endforeach
    </div>
</div>
