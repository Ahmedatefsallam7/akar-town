{{-- Step: e-mail and password. A <form> so the browser fills a saved e-mail and password here only (fields
     outside any form are treated as one form, and the e-mail went into other pages' search boxes). --}}
<form x-show="step === 'email'" x-cloak @submit.prevent class="flex flex-1 flex-col" novalidate>
    <button type="button" @click="go('phone'); $wire.registering && $wire.cancelRegistration()" class="{{ $back }}">{!! $backIcon !!}{{ __('Back') }}</button>
    @if ($registering)
        {{-- A new e-mail: the same step creates the account. --}}
        @if ($newEmail)
        <p class="mb-4 [@media(max-height:780px)]:mb-2.5 text-[13px] leading-[1.5] text-black/55 dark:text-white/50 rtl:font-arabic">{{ __("No account with this e-mail yet: add your name to create one.") }}</p>
        @else
            <p class="mb-4 [@media(max-height:780px)]:mb-2.5 text-[13px] leading-[1.5] text-black/55 dark:text-white/50 rtl:font-arabic">{{ __('Already have an account?') }} <button type="button" wire:click="cancelRegistration" class="cursor-pointer border-none bg-none p-0 font-semibold text-brand underline-offset-2 hover:underline">{{ __('Sign in') }}</button></p>
        @endif
        <label for="auth-name" class="{{ $label }}">{{ __('Full name') }}</label>
        <div class="mb-3 [@media(max-height:780px)]:mb-2 flex flex-col">
            <input id="auth-name" type="text" wire:model="name" autocomplete="name" class="{{ $input }} w-full flex-none" />
            @error('name') <span class="{{ $error }}">{{ $message }}</span> @enderror
        </div>
    @endif
    <label for="auth-email" class="{{ $label }}">{{ __('Email') }}</label>
    <div class="mb-3 [@media(max-height:780px)]:mb-2 flex flex-col">
        <input id="auth-email" type="email" wire:model="email" @input="$wire.newEmail && $wire.cancelRegistration()" autocomplete="email" placeholder="you@example.com" class="{{ $input }} w-full flex-none" />
        @error('email') <span class="{{ $error }}">{{ $message }}</span> @enderror
    </div>
    <label for="auth-password" class="{{ $label }}">{{ __('Password') }}</label>
    <div class="mb-3 [@media(max-height:780px)]:mb-2 flex gap-2">
        <div class="relative flex flex-1">
            <input id="auth-password" :type="showPassword ? 'text' : 'password'" wire:model="password" @keydown.enter="$wire.{{ $registering ? 'register' : 'signIn' }}()" autocomplete="{{ $registering ? 'new-password' : 'current-password' }}" placeholder="{{ __('Enter password') }}" class="{{ $input }} w-full pe-10" />
            <button type="button" @click="showPassword = ! showPassword" class="absolute top-1/2 right-3 flex -translate-y-1/2 cursor-pointer border-none bg-none p-1 text-black/35 hover:text-brand dark:text-white/30 rtl:right-auto rtl:left-3" :aria-label="showPassword ? @js(__('Hide password')) : @js(__('Show password'))">
                <svg x-show="! showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" /><circle cx="12" cy="12" r="3" /></svg>
                <svg x-show="showPassword" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" /><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" /><line x1="1" y1="1" x2="23" y2="23" /></svg>
            </button>
        </div>
        @unless ($registering)
            <button type="button" wire:click="signIn" wire:loading.attr="disabled" class="{{ $goButton }} disabled:opacity-70">{{ __('Sign in') }}</button>
        @endunless
    </div>
    @error('password') <span class="{{ $error }} -mt-2 mb-3">{{ $message }}</span> @enderror
    @unless ($registering)
        <button type="button" @click="go('forgot')" class="-mt-1 mb-1 self-end cursor-pointer border-none bg-none p-0 text-[12px] text-black/45 underline-offset-2 transition-colors duration-200 hover:text-brand hover:underline dark:text-white/40 rtl:font-arabic">{{ __('Forgot password?') }}</button>
    @endunless
    @if ($registering)
        <label for="auth-password-confirmation" class="{{ $label }}">{{ __('Confirm password') }}</label>
        <div class="mb-3 [@media(max-height:780px)]:mb-2 flex flex-col">
            <input id="auth-password-confirmation" :type="showPassword ? 'text' : 'password'" wire:model="passwordConfirmation" @keydown.enter="$wire.register()" autocomplete="new-password" class="{{ $input }} w-full flex-none" />
            @error('passwordConfirmation') <span class="{{ $error }}">{{ $message }}</span> @enderror
        </div>
        <button type="button" wire:click="register" wire:loading.attr="disabled" class="{{ $goButton }} w-full disabled:opacity-70">{{ __('Create account') }}</button>
    @endif
    <div class="{{ $or }}">{{ __('OR') }}</div>
    <div class="flex gap-[10px] max-[620px]:gap-2">
        <button type="button" @click="go('phone')" class="{{ $social }}" title="{{ __('Continue with WhatsApp') }}" aria-label="{{ __('Continue with WhatsApp') }}"><x-website.icons.whatsapp class="text-[#25D366]" /></button>
        <button type="button" @click="comingSoon('Google')" class="{{ $social }}" title="{{ __('Continue with Google') }}" aria-label="{{ __('Continue with Google') }}">{!! $google !!}</button>
        <button type="button" @click="comingSoon('Facebook')" class="{{ $social }}" title="{{ __('Continue with Facebook') }}" aria-label="{{ __('Continue with Facebook') }}">{!! $facebook !!}</button>
    </div>
    <p x-show="soon" x-cloak class="mt-3 text-center text-[12px] text-black/50 dark:text-white/45 rtl:font-arabic" role="status" x-text="@js(__(':provider sign-in is coming soon.', ['provider' => '__P__'])).replace('__P__', soon)"></p>
    @unless ($registering)
        <p class="mt-3 text-center text-[12.5px] text-black/55 dark:text-white/50 rtl:font-arabic [@media(max-height:780px)]:mt-2">{{ __('New to Akar Town?') }} <button type="button" wire:click="startRegistration" class="cursor-pointer border-none bg-none p-0 font-semibold text-brand underline-offset-2 hover:underline">{{ __('Create an account') }}</button></p>
    @endunless
    <p class="{{ $terms }}">{!! __('By registering you accept our <a href=":terms">Terms & Conditions</a> and <a href=":privacy">our privacy policy</a>.', ['terms' => route('terms'), 'privacy' => route('privacy')]) !!}</p>
</form>
