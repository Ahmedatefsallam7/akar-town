{{-- Step: forgot password (not in the design: without it, a forgotten password locks the account) --}}
<div x-show="step === 'forgot'" x-cloak class="flex flex-1 flex-col">
    <button type="button" @click="go('email'); $wire.cancelReset()" class="{{ $back }}">{!! $backIcon !!}{{ __('Back') }}</button>
    @if ($resetSent)
        <div class="py-6 text-center" role="status">
            <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-[rgba(200,16,46,0.08)]">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-7 text-brand" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" /></svg>
            </div>
            <p class="text-[13.5px] leading-[1.6] text-black/60 dark:text-white/55 rtl:font-arabic">{{ __('If this e-mail has an account, a link to choose a new password is on its way. It works for 60 minutes.') }}</p>
        </div>
    @else
        <p class="mb-4 [@media(max-height:780px)]:mb-2.5 text-[13px] leading-[1.5] text-black/55 dark:text-white/50 rtl:font-arabic">{{ __("Enter your e-mail and we'll send you a link to choose a new password.") }}</p>
        <label for="auth-reset-email" class="{{ $label }}">{{ __('Email') }}</label>
        <div class="mb-3 [@media(max-height:780px)]:mb-2 flex flex-col">
            <input id="auth-reset-email" type="email" wire:model="email" @keydown.enter="$wire.sendResetLink()" autocomplete="email" placeholder="you@example.com" class="{{ $input }} w-full flex-none" />
            @error('email') <span class="{{ $error }}">{{ $message }}</span> @enderror
        </div>
        <button type="button" wire:click="sendResetLink" wire:loading.attr="disabled" class="{{ $goButton }} w-full disabled:opacity-70">{{ __('Send link') }}</button>
    @endif
</div>
