{{-- Dashboard greeting + "Add" shortcuts. Tailwind only. --}}
@php
    $user = filament()->auth()->user();
    $shortcuts = $this->getShortcuts();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <x-filament::avatar :src="filament()->getUserAvatarUrl($user)" :alt="filament()->getUserName($user)" size="lg" />

                <div>
                    <h2 class="text-lg font-bold text-gray-950 dark:text-white">
                        {{ __('admin.dashboard.welcome', ['name' => filament()->getUserName($user)]) }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $user->role?->getLocalized('name') ?? __('admin.dashboard.no_role') }} · {{ now()->translatedFormat('l j F Y') }}
                    </p>
                </div>
            </div>

            @if ($shortcuts)
                <div class="flex flex-wrap gap-2">
                    @foreach ($shortcuts as $shortcut)
                        <x-filament::button tag="a" :href="$shortcut['url']" :icon="$shortcut['icon']" color="gray" size="sm">
                            {{ $shortcut['label'] }}
                        </x-filament::button>
                    @endforeach
                </div>
            @endif
        </div>

        @unless ($shortcuts || \App\Filament\Support\PanelResources::visible())
            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.dashboard.no_access') }}</p>
        @endunless
    </x-filament::section>
</x-filament-widgets::widget>
