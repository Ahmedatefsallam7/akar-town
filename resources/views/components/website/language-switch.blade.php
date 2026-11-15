{{--
    The language switch, in the admin's look (resources/views/filament/language-switcher.blade.php): one button per
    language in a pill, a red knob sliding under the chosen one; the page reloads in it once the knob has moved.
    "md" (the navbar's icon buttons' height) or "sm".
--}}
@props(['size' => 'md'])

@php
    $locales = array_keys(config('app.available_locales'));
    $current = array_search(app()->getLocale(), $locales);
    // [--count:2] / [--index:N] put the knob in place before Alpine starts (written out so Tailwind makes them).
@endphp

<div
    x-data="{ active: @js($current) }"
    x-bind:style="{ '--count': {{ count($locales) }}, '--index': active }"
    dir="ltr"
    {{ $attributes->class([
        'inline-flex shrink-0 items-center rounded-full bg-white/8 p-[3px] ring-1 ring-white/15 ring-inset backdrop-blur-[10px] [--count:2]',
        '[--index:0]' => $current === 0,
        '[--index:1]' => $current === 1,
        'h-[31px]' => $size === 'md',
        'h-[28px]' => $size === 'sm',
    ]) }}
    role="group"
    aria-label="{{ __('Switch language') }}"
>
    <div class="relative grid h-full grid-cols-[repeat(var(--count),minmax(0,1fr))]">
        <span class="absolute inset-y-0 start-0 w-[calc(100%/var(--count))] translate-x-[calc(var(--index)*100%)] rounded-full bg-[linear-gradient(135deg,#e0334f_0%,#c8102e_55%,#a00d25_100%)] shadow-md shadow-[rgba(200,16,46,0.45)] transition-transform duration-250 ease-in-out" aria-hidden="true"></span>

        @foreach ($locales as $index => $locale)
            <a
                href="{{ route('locale.switch', $locale) }}"
                hreflang="{{ $locale }}"
                lang="{{ $locale }}"
                title="{{ config('app.available_locales')[$locale] }}"
                @if ($locale === app()->getLocale()) aria-current="true" @endif
                x-on:click.prevent="if (active !== {{ $index }}) { active = {{ $index }}; setTimeout(() => window.location = $el.href, 250) }"
                x-bind:class="active === {{ $index }} ? 'pointer-events-none text-white text-shadow-xs' : 'text-white/70 hover:text-white'"
                @class([
                    'relative z-10 flex items-center justify-center rounded-full font-bold tracking-wide whitespace-nowrap transition-colors duration-200',
                    $locale === app()->getLocale() ? 'pointer-events-none text-white' : 'text-white/70',
                    'font-arabic' => app()->getLocale() === 'ar',
                    'min-w-10 px-2.5 text-[12px]' => $size === 'md',
                    'min-w-9 px-2 text-[11px]' => $size === 'sm',
                ])
            >{{ __(strtoupper($locale)) }}</a>
        @endforeach
    </div>
</div>
