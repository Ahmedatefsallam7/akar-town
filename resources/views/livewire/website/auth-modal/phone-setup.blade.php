{{-- Step: after a right code, the name (a new account) and a password, so next time the password is enough --}}
<form x-show="step === 'phone-setup'" x-cloak @submit.prevent="$wire.completePhoneSetup()" class="flex flex-1 flex-col" novalidate>
    <div class="mb-4 flex items-center gap-2 rounded-[10px] bg-[rgba(37,211,102,0.1)] px-3 py-2.5 text-[12.5px] font-semibold text-[#128c4a] dark:text-[#4ade80] rtl:font-arabic" role="status">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-4 flex-none" aria-hidden="true"><polyline points="20 6 9 17 4 12" /></svg>
        <span><bdi dir="ltr">{{ $e164 }}</bdi> {{ __('is confirmed.') }}</span>
    </div>
    <p class="{{ $note }}">{{ $setup === 'new' ? __('Last step: your name and a password, to sign in with your number next time.') : __('Choose a password, to sign in with your number next time.') }}</p>
    <input type="tel" class="hidden" autocomplete="username" value="{{ $e164 }}" tabindex="-1" aria-hidden="true" />
    @if ($setup === 'new')
        <label for="auth-phone-name" class="{{ $label }}">{{ __('Full name') }}</label>
        <div class="mb-3 [@media(max-height:780px)]:mb-2 flex flex-col">
            <input id="auth-phone-name" type="text" wire:model="name" autocomplete="name" class="{{ $input }} w-full flex-none" />
            @error('name') <span class="{{ $error }}">{{ $message }}</span> @enderror
        </div>
    @endif
    <label for="auth-phone-new-password" class="{{ $label }}">{{ __('Password') }}</label>
    <div class="mb-3 [@media(max-height:780px)]:mb-2 flex flex-col">
        <input id="auth-phone-new-password" :type="showPassword ? 'text' : 'password'" wire:model="password" autocomplete="new-password" placeholder="{{ __('At least 8 characters') }}" class="{{ $input }} w-full flex-none" />
        @error('password') <span class="{{ $error }}">{{ $message }}</span> @enderror
    </div>
    <label for="auth-phone-password-confirmation" class="{{ $label }}">{{ __('Confirm password') }}</label>
    <div class="mb-4 [@media(max-height:780px)]:mb-2.5 flex flex-col">
        <input id="auth-phone-password-confirmation" :type="showPassword ? 'text' : 'password'" wire:model="passwordConfirmation" autocomplete="new-password" class="{{ $input }} w-full flex-none" />
        @error('passwordConfirmation') <span class="{{ $error }}">{{ $message }}</span> @enderror
    </div>
    <button type="submit" wire:loading.attr="disabled" wire:target="completePhoneSetup" class="{{ $goButton }} w-full disabled:opacity-70">{{ $setup === 'new' ? __('Create account') : __('Save and sign in') }}</button>
</form>
