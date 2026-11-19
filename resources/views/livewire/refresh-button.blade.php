{{-- Top bar button: refresh counts, the website's cached lists and numbers, and the open page. --}}
<div>
    <x-filament::icon-button
        wire:click="refresh"
        icon="heroicon-o-arrow-path"
        color="gray"
        size="lg"
        :label="__('admin.refresh.label')"
        :tooltip="__('admin.refresh.tooltip')"
    />
</div>
