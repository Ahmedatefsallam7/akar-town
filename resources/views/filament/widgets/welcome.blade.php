{{-- Dashboard greeting + site status. Tailwind only. --}}
@php
    $user = filament()->auth()->user();
    $live = $this->isSiteLive();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <x-filament::avatar :src="filament()->getUserAvatarUrl($user)" :alt="filament()->getUserName($user)" size="lg" />

                <div>
                    <h2 class="text-lg font-bold text-gray-950">
                        {{ __('admin.dashboard.welcome', ['name' => filament()->getUserName($user)]) }}
                    </h2>
                    <p class="text-sm text-gray-500">
                        {{ __('admin.dashboard.widgets.platform_happening') }}
                    </p>
                    <p class="text-xs text-gray-400">
                        {{ $user->role?->getLocalized('name') ?? __('admin.dashboard.no_role') }} · {{ now()->translatedFormat('l j F Y') }}
                    </p>
                </div>
            </div>

            <div @class([
                'flex items-center gap-3 rounded-xl border px-4 py-2.5',
                'border-emerald-200 bg-emerald-50' => $live,
                'border-red-200 bg-red-50' => ! $live,
            ])>
                <div class="flex flex-col leading-tight">
                    <span class="text-xs font-medium text-gray-500">{{ __('admin.site_status.label') }}</span>
                    <span @class(['text-sm font-semibold', 'text-emerald-700' => $live, 'text-red-700' => ! $live])>
                        {{ $live ? __('admin.site_status.online') : __('admin.site_status.offline') }}
                    </span>
                </div>

                @if ($this->canSwitchSite())
                    <button
                        type="button"
                        role="switch"
                        aria-checked="{{ $live ? 'true' : 'false' }}"
                        aria-label="{{ __('admin.site_status.switch') }}"
                        wire:click="toggleSite"
                        wire:confirm="{{ $live ? __('admin.site_status.confirm_offline') : __('admin.site_status.confirm_online') }}"
                        @class([
                            'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors',
                            'bg-emerald-500 hover:bg-emerald-600' => $live,
                            'bg-red-500 hover:bg-red-600' => ! $live,
                        ])
                    >
                        <span @class([
                            'absolute top-0.5 size-5 rounded-full bg-white shadow transition-all',
                            'start-[1.375rem]' => $live,
                            'start-0.5' => ! $live,
                        ])></span>
                    </button>
                @endif
            </div>
        </div>

        @unless (\App\Filament\Support\PanelResources::visible())
            <p class="mt-4 text-sm text-gray-500">{{ __('admin.dashboard.no_access') }}</p>
        @endunless
    </x-filament::section>
</x-filament-widgets::widget>
