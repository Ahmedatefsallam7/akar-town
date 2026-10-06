{{-- Top bar button (Dastan style): opens the public website in a new tab. The text hides on small screens. --}}
<a
    href="{{ url('/') }}"
    target="_blank"
    rel="noopener"
    aria-label="{{ __('admin.navigation.go_to_website') }}"
    class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-[#0a1e77] px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#08175e] sm:px-4"
>
    <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="size-4 rtl:-scale-x-100" />
    <span class="hidden sm:inline">{{ __('admin.navigation.go_to_website') }}</span>
</a>
