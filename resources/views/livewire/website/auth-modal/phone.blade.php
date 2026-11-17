{{-- Step: WhatsApp number --}}
<div x-show="step === 'phone'" class="flex flex-1 flex-col">
    {{-- The number checked as it's typed (phoneCheck): green with a tick once it's right, red when it can't be. --}}
    @php($check = $this->phoneCheck())
    @php($wrong = in_array($check['state'], ['long', 'invalid'], true) || $errors->has('phone'))
    <label for="auth-phone" class="{{ $label }} flex items-center gap-1.5">
        <x-website.icons.whatsapp class="size-4 text-[#25D366]" />
        {{ __('WhatsApp number') }}
    </label>
    <div>
    {{-- One field: the country and the number together (left to right, as numbers are written). Its edge
         turns green once the number is right and red when it can't be. --}}
    <div
        dir="ltr"
        x-ref="field"
        @class([
            'relative flex h-[52px] items-stretch rounded-[14px] border-[1.5px] bg-white transition-[border-color,box-shadow] duration-200 dark:bg-[#1f1f1f] [@media(max-height:780px)]:h-12',
            'border-[#e0ddd8] focus-within:border-brand focus-within:shadow-[0_0_0_4px_rgba(200,16,46,0.10)] dark:border-white/10' => $check['state'] !== 'ok' && ! $wrong,
            'border-[#16a34a] shadow-[0_0_0_4px_rgba(22,163,74,0.12)]' => $check['state'] === 'ok',
            'border-danger shadow-[0_0_0_4px_rgba(200,16,46,0.10)]' => $wrong,
        ])
    >
        <div class="relative">
            <button type="button" @click="dial ? closeDial() : openDial()" :aria-expanded="dial" aria-haspopup="listbox" class="flex h-full cursor-pointer items-center gap-1.5 rounded-l-[13px] ps-3.5 pe-2.5 text-[#0d0d0d] transition-[background] duration-200 hover:bg-black/[0.03] dark:text-[#f0ede8] dark:hover:bg-white/5" aria-label="{{ __('Select country code') }}">
                <span class="text-[19px] leading-none" x-text="countries[country][1]"></span>
                <span class="text-[14px] font-semibold tabular-nums" x-text="countries[country][2]"></span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-3 text-black/35 transition-transform duration-200 dark:text-white/35" :class="dial && 'rotate-180'" aria-hidden="true"><polyline points="6 9 12 15 18 9" /></svg>
            </button>
        </div>
        <span class="my-3 w-px flex-none bg-black/10 dark:bg-white/10" aria-hidden="true"></span>
        <input id="auth-phone" x-ref="phone" wire:model.live.debounce.300ms="phone" type="tel" inputmode="tel" maxlength="20" autocomplete="tel" placeholder="{{ __('Your mobile number') }}" aria-describedby="auth-phone-hint" @if ($wrong) aria-invalid="true" @endif
            class="h-full min-w-0 flex-1 bg-transparent px-3 font-[inherit] text-[16px] font-medium tracking-[0.03em] text-[#0d0d0d] tabular-nums outline-none placeholder:font-normal placeholder:tracking-normal placeholder:text-black/30 dark:text-[#f0ede8] dark:placeholder:text-white/25"
            @keydown.enter="$wire.continueWithPhone()" />
        {{-- The field's own sign: a green tick once right, a red mark when it can't be. --}}
        <span class="flex flex-none items-center pe-3.5" aria-hidden="true">
            @if ($check['state'] === 'ok')
                <span class="flex size-6 items-center justify-center rounded-full bg-[#16a34a] text-white"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" class="size-3.5"><polyline points="20 6 9 17 4 12" /></svg></span>
            @elseif ($wrong)
                <span class="flex size-6 items-center justify-center rounded-full bg-danger text-[13px] font-bold text-white">!</span>
            @endif
        </span>
    </div>
    </div>
    {{-- One line under the field: why WhatsApp and how many digits; then whether the number is right. --}}
    @php($rules = \App\Livewire\Website\AuthModal::phoneRules($country))
    <p id="auth-phone-hint" class="mt-2 flex min-h-[18px] items-start gap-1.5 text-[12.5px] leading-[1.5] rtl:font-arabic" aria-live="polite">
        @if ($check['state'] === 'ok')
            <span class="font-semibold text-[#15803d] dark:text-[#4ade80]">{{ __('The number is right.') }}</span>
        @elseif ($wrong && ! $errors->has('phone'))
            <span class="text-danger">{{ __('This number isn’t right.') }}</span>
        @elseif (! $errors->has('phone'))
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-[3px] size-[13px] flex-none text-black/35 dark:text-white/35" aria-hidden="true"><circle cx="12" cy="12" r="10" /><line x1="12" y1="16" x2="12" y2="12" /><line x1="12" y1="8" x2="12.01" y2="8" /></svg>
            <span class="text-black/50 dark:text-white/45">{{ trans_choice('It must have WhatsApp: we’ll send your code there (:count digit).|It must have WhatsApp: we’ll send your code there (:count digits).', $length = strlen($rules['trunk']) + $rules['digits'], ['count' => $length]) }}</span>
        @endif
    </p>
    @error('phone') <span class="{{ $error }}" role="alert">{{ $message }}</span> @enderror
    @if ($sendFailed)
        <button type="button" @click="go('email')" class="mt-2 self-start cursor-pointer border-none bg-none p-0 text-[12.5px] font-semibold text-brand underline-offset-2 hover:underline rtl:font-arabic">{{ __('Sign in with e-mail instead') }}</button>
    @endif
    <button type="button" wire:click="continueWithPhone" wire:loading.attr="disabled" wire:target="continueWithPhone" class="{{ $goButton }} mt-4 flex h-12! w-full items-center justify-center gap-2 disabled:opacity-70 [@media(max-height:780px)]:mt-3">
        <x-website.icons.whatsapp class="size-[18px]" wire:loading.remove wire:target="continueWithPhone" />
        <span wire:loading wire:target="continueWithPhone" class="size-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true"></span>
        {{ __('Continue with WhatsApp') }}
    </button>
    <div class="{{ $or }}">{{ __('OR') }}</div>
    <div class="flex gap-[10px] max-[620px]:gap-2">
        <button type="button" @click="go('email')" class="{{ $social }}" title="{{ __('Continue with Email') }}" aria-label="{{ __('Continue with Email') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="text-[#0d0d0d] dark:text-[#f0ede8]" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" /></svg>
        </button>
        <button type="button" @click="comingSoon('Google')" class="{{ $social }}" title="{{ __('Continue with Google') }}" aria-label="{{ __('Continue with Google') }}">{!! $google !!}</button>
        <button type="button" @click="comingSoon('Facebook')" class="{{ $social }}" title="{{ __('Continue with Facebook') }}" aria-label="{{ __('Continue with Facebook') }}">{!! $facebook !!}</button>
    </div>
    {{-- Registering is easy to find (user: a visitor didn't know a new e-mail creates an account). --}}
    <p class="mt-3 text-center text-[12.5px] text-black/55 dark:text-white/50 rtl:font-arabic [@media(max-height:780px)]:mt-2">{{ __('New to Akar Town?') }} <button type="button" @click="go('email'); $wire.startRegistration()" class="cursor-pointer border-none bg-none p-0 font-semibold text-brand underline-offset-2 hover:underline">{{ __('Create an account') }}</button></p>
    <p x-show="soon" x-cloak class="mt-3 text-center text-[12px] text-black/50 dark:text-white/45 rtl:font-arabic" role="status" x-text="@js(__(':provider sign-in is coming soon.', ['provider' => '__P__'])).replace('__P__', soon)"></p>
    <p class="{{ $terms }}">{!! __('By registering you accept our <a href=":terms">Terms & Conditions</a> and <a href=":privacy">our privacy policy</a>.', ['terms' => route('terms'), 'privacy' => route('privacy')]) !!}</p>
</div>
