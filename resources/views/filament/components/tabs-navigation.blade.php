{{-- Previous / Next for the surrounding Tabs: uses its Alpine state (tab, getTabs()), which holds only the visible tabs. --}}
@php
    $rtl = __('filament-panels::layout.direction') === 'rtl';
@endphp

<div class="flex items-center gap-3 border-t border-gray-200 pt-4">
    <x-filament::button
        type="button"
        color="gray"
        :icon="$rtl ? 'heroicon-m-arrow-right' : 'heroicon-m-arrow-left'"
        x-cloak
        x-show="getTabs().indexOf(tab) > 0"
        x-on:click="tab = getTabs()[getTabs().indexOf(tab) - 1]; $el.closest('.fi-sc-tabs')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
    >
        {{ __('admin.actions.previous') }}
    </x-filament::button>

    <x-filament::button
        type="button"
        class="ms-auto"
        :icon="$rtl ? 'heroicon-m-arrow-left' : 'heroicon-m-arrow-right'"
        icon-position="after"
        x-cloak
        x-show="getTabs().indexOf(tab) < getTabs().length - 1"
        x-on:click="tab = getTabs()[getTabs().indexOf(tab) + 1]; $el.closest('.fi-sc-tabs')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
    >
        {{ __('admin.actions.next') }}
    </x-filament::button>
</div>
