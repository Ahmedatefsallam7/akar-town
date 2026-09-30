@php
    $tools = [
        [
            'action' => 'refreshAppLists',
            'icon' => 'heroicon-o-sparkles',
            'title' => __('admin.utilities.refresh_lists'),
            'description' => __('admin.utilities.refresh_lists_desc'),
        ],
        [
            'action' => 'refreshPermissions',
            'icon' => 'heroicon-o-shield-check',
            'title' => __('admin.utilities.refresh_permissions'),
            'description' => __('admin.utilities.refresh_permissions_desc'),
        ],
    ];
@endphp

<x-filament-panels::page>
    <div class="divide-y divide-gray-200 overflow-hidden rounded-xl bg-white shadow-xs ring-1 ring-gray-950/5 dark:divide-white/10 dark:bg-gray-900 dark:ring-white/10">
        @foreach ($tools as $tool)
            <button
                type="button"
                wire:click="mountAction('{{ $tool['action'] }}')"
                class="flex w-full items-center gap-4 px-6 py-4 text-start transition hover:bg-gray-50 focus-visible:bg-gray-50 focus-visible:outline-none dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
            >
                <span class="flex size-12 shrink-0 items-center justify-center rounded-full ring-1 ring-gray-300 dark:ring-white/20">
                    <x-filament::icon :icon="$tool['icon']" class="size-6 text-gray-600 dark:text-gray-300" />
                </span>

                <span class="flex flex-col">
                    <span class="text-base font-semibold text-gray-950 dark:text-white">{{ $tool['title'] }}</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $tool['description'] }}</span>
                </span>

                <x-filament::icon
                    icon="heroicon-m-chevron-right"
                    class="ms-auto size-5 text-gray-400 rtl:rotate-180"
                />
            </button>
        @endforeach
    </div>
</x-filament-panels::page>
