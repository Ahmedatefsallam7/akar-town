{{-- Step: the code didn't arrive (Meta's webhook): the number has no WhatsApp, or another reason --}}
<div x-show="step === 'undelivered'" x-cloak class="flex flex-1 flex-col" role="alert">
    <button type="button" @click="go('phone')" class="{{ $back }}">{!! $backIcon !!}{{ __('Back') }}</button>
    <div class="mb-4 flex items-start gap-3">
        <span class="flex size-11 flex-none items-center justify-center rounded-full bg-[rgba(200,16,46,0.08)] text-brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" /><line x1="9" y1="8" x2="15" y2="14" /><line x1="15" y1="8" x2="9" y2="14" /></svg>
        </span>
        <div>
            <p class="mb-1 text-[15px] font-bold text-[#0d0d0d] dark:text-[#f0ede8] rtl:font-arabic">{{ $delivery === 'no_whatsapp' ? __("This number isn't on WhatsApp") : __("The code didn't arrive") }}</p>
            <p class="mb-1 text-[13px] font-semibold text-[#0d0d0d] dark:text-[#f0ede8]"><bdi dir="ltr">{{ $e164 }}</bdi></p>
            <p class="text-[12.5px] leading-[1.55] text-black/55 dark:text-white/50 rtl:font-arabic">{{ $delivery === 'no_whatsapp' ? __('WhatsApp says this number can’t receive messages. Check the number, or sign in another way.') : __('WhatsApp couldn’t deliver the code. Try again, or sign in another way.') }}</p>
        </div>
    </div>
    <div class="mb-4 flex flex-wrap gap-2">
        <button type="button" @click="go('phone'); $nextTick(() => $refs.phone?.focus())" class="{{ $goButton }} flex-1">{{ __('Change the number') }}</button>
        @if ($delivery === 'failed')
            <button type="button" wire:click="sendCode" wire:loading.attr="disabled" wire:target="sendCode" class="h-11 flex-1 cursor-pointer rounded-[10px] border-[1.5px] border-[#e0ddd8] bg-white px-4 text-[13.5px] font-bold text-[#0d0d0d] transition-[border-color] duration-200 hover:border-brand disabled:opacity-70 dark:border-white/10 dark:bg-[#1f1f1f] dark:text-[#f0ede8] rtl:font-arabic">{{ __('Send again') }}</button>
        @endif
    </div>
    @error('phone') <span class="{{ $error }} -mt-2 mb-3" role="alert">{{ $message }}</span> @enderror
    <p class="{{ $label }}">{{ __('Or sign in another way') }}</p>
    <div class="flex gap-[10px] max-[620px]:gap-2">
        <button type="button" @click="go('email')" class="{{ $social }}" title="{{ __('Continue with Email') }}" aria-label="{{ __('Continue with Email') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="text-[#0d0d0d] dark:text-[#f0ede8]" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" /></svg>
        </button>
        <button type="button" @click="comingSoon('Google')" class="{{ $social }}" title="{{ __('Continue with Google') }}" aria-label="{{ __('Continue with Google') }}">{!! $google !!}</button>
        <button type="button" @click="comingSoon('Facebook')" class="{{ $social }}" title="{{ __('Continue with Facebook') }}" aria-label="{{ __('Continue with Facebook') }}">{!! $facebook !!}</button>
    </div>
    <p x-show="soon" x-cloak class="mt-3 text-center text-[12px] text-black/50 dark:text-white/45 rtl:font-arabic" role="status" x-text="@js(__(':provider sign-in is coming soon.', ['provider' => '__P__'])).replace('__P__', soon)"></p>
</div>
