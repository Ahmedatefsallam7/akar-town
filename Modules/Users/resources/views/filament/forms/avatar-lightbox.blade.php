{{-- Shows the saved photo large when it's clicked (the avatar field dispatches "open-avatar"). Alpine + Tailwind only. --}}
@php
    $url = $getRecord()?->getFilamentAvatarUrl();
@endphp

@if ($url)
    <div x-data="{ open: false }" x-on:open-avatar.window="open = true" x-on:keydown.escape.window="open = false">
        <template x-teleport="body">
            <div
                x-show="open"
                x-transition.opacity.duration.200ms
                x-on:click="open = false"
                role="dialog"
                aria-modal="true"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/80 p-4"
                x-cloak
            >
                <img
                    src="{{ $url }}"
                    alt="{{ __('admin.users.avatar') }}"
                    x-on:click.stop
                    x-show="open"
                    x-transition.scale.90.duration.200ms
                    class="size-72 rounded-2xl object-cover shadow-2xl ring-1 ring-white/10 sm:size-96"
                >

                <button
                    type="button"
                    x-on:click="open = false"
                    aria-label="{{ __('filament-actions::modal.actions.close.label') }}"
                    class="absolute end-4 top-4 flex size-10 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20"
                >
                    <x-filament::icon icon="heroicon-m-x-mark" class="size-6" />
                </button>
            </div>
        </template>
    </div>
@endif
