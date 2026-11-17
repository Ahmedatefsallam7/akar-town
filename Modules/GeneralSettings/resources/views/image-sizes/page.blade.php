{{--
    Settings → Image Sizes (AKAR-50): one card per image, by section. The top of a card draws the image's shape with
    its size inside; under it three short lines (smallest image, files, where it is shown) and the Edit button.
    Search and "changed only" filter the cards in the browser (no server trip); a section opens by itself while
    filtering.
--}}
@php
    $sections = $this->getSections();
    $cards = collect($sections)->flatMap(fn (array $section) => $section['cards']);
    $changed = $cards->where('changed', true)->count();
@endphp

<x-filament-panels::page>
    <div
        x-data="{
            q: '',
            onlyChanged: false,
            shows(search, changed) {
                return (! this.onlyChanged || changed) && (this.q.trim() === '' || search.includes(this.q.trim().toLowerCase()))
            },
            get filtering() { return this.q.trim() !== '' || this.onlyChanged },
        }"
        class="space-y-4"
    >
        {{-- Search, the summary, "changed only", and open / close all. --}}
        <div class="flex flex-wrap items-center gap-3">
            <div class="min-w-60 flex-1">
                <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                    <x-filament::input type="search" x-model="q" :placeholder="__('admin.image_sizes.search')" />
                </x-filament::input.wrapper>
            </div>

            <span class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.image_sizes.summary', ['count' => $cards->count()]) }}
                @if ($changed)
                    · <span class="font-semibold text-warning-600 dark:text-warning-400">{{ __('admin.image_sizes.summary_changed', ['count' => $changed]) }}</span>
                @endif
            </span>

            @if ($changed)
                <button
                    type="button"
                    x-on:click="onlyChanged = ! onlyChanged"
                    x-bind:class="onlyChanged ? 'bg-warning-50 text-warning-700 ring-warning-600/30 dark:bg-warning-400/10 dark:text-warning-300' : 'bg-white text-gray-700 ring-gray-950/10 dark:bg-white/5 dark:text-gray-200 dark:ring-white/20'"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium ring-1 transition"
                >
                    <x-filament::icon icon="heroicon-m-funnel" class="size-4" />
                    {{ __('admin.image_sizes.only_changed') }}
                </button>
            @endif

            <div class="ms-auto flex gap-4 text-sm font-medium" x-show="! filtering">
                <button type="button" x-on:click="$dispatch('image-sizes-open', true)" class="text-primary-600 hover:underline dark:text-primary-400">{{ __('admin.image_sizes.open_all') }}</button>
                <button type="button" x-on:click="$dispatch('image-sizes-open', false)" class="text-gray-500 hover:underline dark:text-gray-400">{{ __('admin.image_sizes.close_all') }}</button>
            </div>
        </div>

        @foreach ($sections as $group => $section)
            {{-- One section per group, opened and closed from its header (the first one starts open). --}}
            <section
                wire:key="section-{{ $group }}"
                x-data="{ open: @js($loop->first), cards: @js(array_map(fn ($card) => [$card['search'], $card['changed']], $section['cards'])) }"
                x-on:image-sizes-open.window="open = $event.detail"
                x-show="cards.some(([search, changed]) => shows(search, changed))"
                class="overflow-hidden rounded-2xl bg-white shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
            >
                <button
                    type="button"
                    x-on:click="open = ! open"
                    x-bind:aria-expanded="open || filtering"
                    class="flex w-full items-center gap-3 px-5 py-4 text-start transition hover:bg-gray-50 dark:hover:bg-white/5"
                >
                    <span class="text-lg font-bold text-gray-950 dark:text-white">{{ $section['name'] }}</span>
                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-white/10 dark:text-gray-300">{{ count($section['cards']) }}</span>
                    @if (collect($section['cards'])->contains('changed', true))
                        <x-filament::badge color="warning" size="sm">{{ __('admin.image_sizes.status.changed') }}</x-filament::badge>
                    @endif
                    <x-filament::icon icon="heroicon-m-chevron-down" class="ms-auto size-5 text-gray-400 transition duration-200" x-bind:class="(open || filtering) && 'rotate-180'" />
                </button>

                <div x-show="open || filtering" x-collapse x-cloak class="border-t border-gray-100 bg-gray-50/60 p-5 dark:border-white/10 dark:bg-white/[0.02]">
                    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($section['cards'] as $card)
                            <article
                                wire:key="card-{{ $card['key'] }}"
                                x-show="shows(@js($card['search']), @js($card['changed']))"
                                @class([
                                    'flex flex-col overflow-hidden rounded-2xl bg-white shadow-xs ring-1 transition duration-200 hover:-translate-y-0.5 hover:shadow-lg dark:bg-gray-900',
                                    'ring-gray-950/5 dark:ring-white/10' => ! $card['changed'],
                                    'ring-2 ring-warning-400/70 dark:ring-warning-400/50' => $card['changed'],
                                ])
                            >
                                @include('generalsettings::image-sizes.drawing', ['size' => $card['size'], 'ratio' => $card['ratio'], 'drawing' => $card['drawing'], 'thin' => $card['thin']])

                                <div class="flex flex-1 flex-col gap-3 p-5">
                                    <div class="flex items-start justify-between gap-2">
                                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ $card['name'] }}</h3>
                                        @if ($card['changed'])
                                            <x-filament::badge color="warning" size="sm">{{ __('admin.image_sizes.status.changed') }}</x-filament::badge>
                                        @endif
                                    </div>

                                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                                        <li class="flex items-start gap-2">
                                            <x-filament::icon icon="heroicon-o-arrows-pointing-in" class="mt-0.5 size-4 shrink-0 text-gray-400" />
                                            <span>{{ $card['smallest'] }}</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <x-filament::icon icon="heroicon-o-document" class="mt-0.5 size-4 shrink-0 text-gray-400" />
                                            <span>{{ $card['file'] }}</span>
                                        </li>
                                        <li class="flex items-start gap-2" title="{{ $card['shown_on_all'] }}">
                                            <x-filament::icon icon="heroicon-o-map-pin" class="mt-0.5 size-4 shrink-0 text-gray-400" />
                                            <span>{{ __('admin.image_sizes.card.shown_on', ['places' => $card['shown_on']]) }}</span>
                                        </li>
                                    </ul>
                                </div>

                                @if ($this->canEdit())
                                    <div class="flex items-center justify-between gap-3 border-t border-gray-100 px-5 py-3 dark:border-white/10">
                                        {{ ($this->editAction)(['field' => $card['key']]) }}
                                        {{ ($this->resetAction)(['field' => $card['key']]) }}
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endforeach

        {{-- Nothing matches the search. --}}
        <div x-show="filtering && ! @js($cards->map(fn ($card) => [$card['search'], $card['changed']])->values()).some(([search, changed]) => shows(search, changed))" x-cloak class="rounded-2xl bg-white px-5 py-12 text-center ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <x-filament::icon icon="heroicon-o-photo" class="mx-auto size-10 text-gray-300 dark:text-gray-600" />
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.image_sizes.no_results') }}</p>
        </div>
    </div>
</x-filament-panels::page>
