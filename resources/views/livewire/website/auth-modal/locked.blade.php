{{-- Step: the number is locked after 3 wrong codes (until an admin unlocks it): contact us, or another way in --}}
<div x-show="step === 'locked'" x-cloak class="flex flex-1 flex-col" role="alert">
    <button type="button" @click="go('phone')" class="{{ $back }}">{!! $backIcon !!}{{ __('Back') }}</button>
    <div class="mb-4 flex items-start gap-3">
        <span class="flex size-11 flex-none items-center justify-center rounded-full bg-[rgba(200,16,46,0.08)] text-brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" /></svg>
        </span>
        <div>
            <p class="mb-1 text-[15px] font-bold text-[#0d0d0d] dark:text-[#f0ede8] rtl:font-arabic">{{ __("You can't continue with WhatsApp") }}</p>
            <p class="mb-1 text-[13px] font-semibold text-[#0d0d0d] dark:text-[#f0ede8]"><bdi dir="ltr">{{ $e164 }}</bdi></p>
            <p class="text-[12.5px] leading-[1.55] text-black/55 dark:text-white/50 rtl:font-arabic">{{ __('For your security, this number was locked after 3 wrong codes. Contact us to unlock it, or sign in another way.') }}</p>
        </div>
    </div>
    <p class="{{ $label }}">{{ __('Contact us') }}</p>
    <div class="mb-4 grid grid-cols-3 gap-2 max-[420px]:grid-cols-1">
        <a href="{{ route('contact') }}" class="{{ $social }} gap-2 text-[12.5px] font-semibold text-[#0d0d0d] dark:text-[#f0ede8] rtl:font-arabic">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4! text-brand" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" /></svg>
            {{ __('Contact page') }}
        </a>
        @if ($business->hotline ?: $business->phone_primary)
            <a href="tel:{{ preg_replace('/[^\d+]/', '', $business->hotline ?: $business->phone_primary) }}" class="{{ $social }} gap-2 text-[12.5px] font-semibold text-[#0d0d0d] dark:text-[#f0ede8] rtl:font-arabic">
                <x-website.icons.phone class="size-4! text-brand" />
                <span dir="ltr">{{ $business->hotline ?: $business->phone_primary }}</span>
            </a>
        @endif
        @if ($business->whatsapp)
            <a href="https://wa.me/{{ preg_replace('/\D/', '', $business->whatsapp) }}" target="_blank" rel="noopener" class="{{ $social }} gap-2 text-[12.5px] font-semibold text-[#0d0d0d] dark:text-[#f0ede8] rtl:font-arabic">
                <x-website.icons.whatsapp class="size-4! text-[#25D366]" />
                {{ __('WhatsApp') }}
            </a>
        @endif
    </div>
    <p class="{{ $label }}">{{ __('Or sign in another way') }}</p>
    <div class="flex gap-[10px] max-[620px]:gap-2">
        <button type="button" @click="go('email')" class="{{ $social }}" title="{{ __('Continue with Email') }}" aria-label="{{ __('Continue with Email') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="text-[#0d0d0d] dark:text-[#f0ede8]" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" /></svg>
        </button>
        @include('livewire.website.auth-modal.provider', ['provider' => 'google', 'icon' => $google])
        @include('livewire.website.auth-modal.provider', ['provider' => 'facebook', 'icon' => $facebook])
    </div>
    <p x-show="soon" x-cloak class="mt-3 text-center text-[12px] text-black/50 dark:text-white/45 rtl:font-arabic" role="status" x-text="@js(__(':provider sign-in is coming soon.', ['provider' => '__P__'])).replace('__P__', soon)"></p>
</div>
