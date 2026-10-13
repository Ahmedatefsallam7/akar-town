{{-- User menu header: avatar, full name, email and role. Tailwind classes only; whitespace-normal lets the name wrap inside Filament's one-line header. --}}
@php
    $user = filament()->auth()->user();

    // Full class strings so Tailwind can find them.
    $roleClasses = match ($user->roleColor()) {
        'danger' => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30',
        'info' => 'bg-info-50 text-info-700 ring-info-600/20 dark:bg-info-400/10 dark:text-info-400 dark:ring-info-400/30',
        'success' => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30',
        default => 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-white/5 dark:text-gray-300 dark:ring-white/20',
    };

    $dotClasses = match ($user->roleColor()) {
        'danger' => 'bg-danger-500',
        'info' => 'bg-info-500',
        'success' => 'bg-success-500',
        default => 'bg-gray-400',
    };
@endphp

<div class="flex items-center gap-3 py-1 whitespace-normal">
    <x-filament::avatar
        :src="filament()->getUserAvatarUrl($user)"
        :alt="filament()->getUserName($user)"
        size="lg"
        class="shrink-0 ring-2 ring-primary-500/30"
    />

    <div class="min-w-0 flex-1">
        <div class="text-sm font-semibold break-words text-gray-950 dark:text-white">
            {{ filament()->getUserName($user) }}
        </div>

        <div class="truncate text-xs font-normal text-gray-500 dark:text-gray-400" title="{{ $user->email }}">
            {{ $user->email }}
        </div>

        @if ($user->role)
            <div @class(['mt-2 inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset', $roleClasses])>
                <div @class(['size-1.5 rounded-full', $dotClasses])></div>
                {{ $user->role->getLocalized('name') }}
            </div>
        @endif
    </div>
</div>
