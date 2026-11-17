{{-- Step: welcome --}}
<div x-show="step === 'success'" x-cloak class="flex flex-1 flex-col">
    <div class="py-8 text-center">
        <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-[rgba(200,16,46,0.08)]">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-7 text-brand" aria-hidden="true"><polyline points="20 6 9 17 4 12" /></svg>
        </div>
        <p class="mb-2 text-[18px] font-bold text-[#0d0d0d] dark:text-[#f0ede8] rtl:font-arabic" x-text="welcome ? @js(__('Welcome, :name!', ['name' => '__N__'])).replace('__N__', welcome) : @js(__('Welcome back!'))">{{ __('Welcome back!') }}</p>
        <p class="text-[13px] text-black/45 dark:text-white/40 rtl:font-arabic">{{ __("You're now signed in.") }}</p>
    </div>
</div>
