@php
    $locales = array_keys(config('app.available_locales'));
@endphp

<div
    x-data="{ active: {{ Js::from(array_search(app()->getLocale(), $locales)) }} }"
    x-bind:style="{ '--count': {{ count($locales) }}, '--index': active }"
    dir="ltr"
    class="inline-flex h-9 items-center rounded-full bg-gray-50 p-[3px] ring-1 ring-gray-200 ring-inset dark:bg-gray-800/60 dark:ring-white/10"
>
    <div class="relative grid h-full grid-cols-[repeat(var(--count),minmax(0,1fr))]">
        <span
            class="absolute inset-y-0 start-0 w-[calc(100%/var(--count))] translate-x-[calc(var(--index)*100%)] rounded-full bg-linear-to-br from-primary-500 to-primary-700 shadow-md shadow-primary-600/40 transition-transform duration-250 ease-in-out"
        ></span>

        @foreach ($locales as $index => $locale)
            <a
                href="{{ route('locale.switch', $locale) }}"
                title="{{ config('app.available_locales')[$locale] }}"
                x-on:click="active = {{ $index }}"
                x-bind:class="active === {{ $index }}
                    ? 'pointer-events-none text-white text-shadow-xs'
                    : 'text-gray-700 hover:text-gray-900 dark:text-gray-100 dark:hover:text-white'"
                class="relative z-10 flex min-w-11 items-center justify-center rounded-full px-3 text-[0.8125rem] font-bold tracking-wide transition-colors duration-200"
            >
                {{ __(strtoupper($locale)) }}
            </a>
        @endforeach
    </div>
</div>
