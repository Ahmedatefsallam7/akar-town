{{-- Lookup types as cards. Card filter: Alpine (no server trip). Super Search: Livewire, across all types. --}}
@php
    $types = $this->getTypes();
    $results = $this->getSuperSearchResults();
@endphp

<x-filament-panels::page>
    <div x-data="{ q: '' }" class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2">
            <x-filament::input.wrapper prefix-icon="heroicon-m-funnel">
                <x-filament::input type="search" x-model="q" :placeholder="__('admin.lookups.search_types')" />
            </x-filament::input.wrapper>

            <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                <x-filament::input type="search" wire:model.live.debounce.400ms="superSearch" :placeholder="__('admin.lookups.super_search')" />
            </x-filament::input.wrapper>
        </div>

        @if (mb_strlen(trim($superSearch)) >= 2)
            <x-filament::section :heading="__('admin.lookups.super_search_results')" compact>
                @forelse ($results as $result)
                    <a href="{{ $result['url'] }}" wire:navigate class="flex items-center justify-between gap-4 rounded-lg px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-white/5">
                        <span class="font-medium text-gray-950 dark:text-white">{{ $result['name'] }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.lookups.in_type', ['name' => $result['in']]) }}</span>
                    </a>
                @empty
                    <p class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.lookups.no_results') }}</p>
                @endforelse
            </x-filament::section>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($types as $type)
                <a
                    href="{{ $type['url'] }}"
                    wire:navigate
                    x-show="! q || @js($type['search']).includes(q.toLowerCase())"
                    class="group flex items-center justify-between gap-3 rounded-xl bg-white p-4 shadow-xs ring-1 ring-gray-950/5 transition hover:ring-primary-500/50 dark:bg-gray-900 dark:ring-white/10"
                >
                    <div class="min-w-0">
                        <div class="truncate font-semibold text-gray-950 group-hover:text-primary-600 dark:text-white dark:group-hover:text-primary-400">{{ $type['name'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ trans_choice('admin.lookups.values_count', $type['count'], ['count' => $type['count']]) }}</div>
                    </div>

                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary-50 text-sm font-bold text-primary-700 dark:bg-primary-400/10 dark:text-primary-300">{{ $type['count'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
