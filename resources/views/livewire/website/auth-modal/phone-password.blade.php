{{-- Step: the WhatsApp number's password (a number that already has one) --}}
<form x-show="step === 'phone-password'" x-cloak @submit.prevent="$wire.signInWithPhone()" class="flex flex-1 flex-col" novalidate>
    <button type="button" @click="go('phone')" class="{{ $back }}">{!! $backIcon !!}{{ __('Use another number') }}</button>
    <p class="{{ $note }}">{{ __('Welcome back! Enter the password of') }} <strong><bdi dir="ltr">{{ $e164 }}</bdi></strong></p>
    <input type="tel" class="hidden" autocomplete="username" value="{{ $e164 }}" tabindex="-1" aria-hidden="true" />
    <label for="auth-phone-password" class="{{ $label }}">{{ __('Password') }}</label>
    <div class="mb-2 flex gap-2">
        <input id="auth-phone-password" :type="showPassword ? 'text' : 'password'" wire:model="password" autocomplete="current-password" placeholder="{{ __('Enter password') }}" class="{{ $input }} w-full" />
        <button type="submit" wire:loading.attr="disabled" wire:target="signInWithPhone" class="{{ $goButton }} disabled:opacity-70">{{ __('Sign in') }}</button>
    </div>
    @error('password') <span class="{{ $error }} mb-2" role="alert">{{ $message }}</span> @enderror
    @error('phone') <span class="{{ $error }} mb-2" role="alert">{{ $message }}</span> @enderror
    <button type="button" wire:click="forgotPhonePassword" class="{{ $link }} self-end">{{ __('Forgot password? Get a code on WhatsApp') }}</button>
</form>
