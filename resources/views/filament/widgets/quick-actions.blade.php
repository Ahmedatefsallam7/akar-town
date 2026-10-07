{{-- Dashboard shortcuts to the most used lists. Tailwind only. --}}
<x-filament-widgets::widget>
    <x-filament::section :heading="__('admin.dashboard.widgets.quick_actions')" compact>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach (static::actions() as $action)
                <a
                    href="{{ $action['url'] }}"
                    class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-700 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700"
                >
                    <x-filament::icon :icon="$action['icon']" class="size-5 shrink-0 text-primary-600" />
                    <span class="truncate">{{ $action['label'] }}</span>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
