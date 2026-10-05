{{-- Top bar button: opens the public website in a new tab. --}}
<x-filament::icon-button
    tag="a"
    :href="url('/')"
    target="_blank"
    rel="noopener"
    icon="heroicon-o-globe-alt"
    color="gray"
    size="lg"
    :label="__('admin.navigation.go_to_website')"
    :tooltip="__('admin.navigation.go_to_website')"
/>
