{{--
    "Sign in or Register" (App\Livewire\Website\AuthModal), as the design's auth-modal: a dark panel with the benefits
    (hidden on phones) and the steps: WhatsApp number, 6-digit code, e-mail and password, welcome. Opened by the
    window event "open-auth"; Escape, the × and a click outside close it. Google / Facebook aren't connected yet:
    they say "coming soon".
--}}
@php
    $countries = \App\Livewire\Website\AuthModal::COUNTRIES;
    $label = 'mb-2 block text-[12.5px] font-semibold tracking-[0.01em] text-[#0d0d0d] dark:text-[#d8d4ce] rtl:font-arabic';
    $input = 'h-12 min-w-0 flex-1 rounded-[10px] border-[1.5px] border-[#e0ddd8] bg-white px-[14px] font-[inherit] text-[14px] text-[#0d0d0d] outline-none transition-[border-color] duration-200 placeholder:text-black/28 focus:border-brand dark:border-white/10 dark:bg-[#1f1f1f] dark:text-[#f0ede8] dark:placeholder:text-white/20';
    $goButton = 'h-12 flex-none cursor-pointer rounded-[10px] border-none bg-brand px-[22px] text-[14px] font-bold tracking-[0.02em] whitespace-nowrap text-white transition-[background,translate] duration-200 hover:-translate-y-px hover:bg-[#a50d26] active:translate-y-0 max-[420px]:px-4 rtl:font-arabic';
    $back = 'mb-[18px] inline-flex cursor-pointer items-center gap-1.5 self-start border-none bg-none p-0 text-[12px] text-black/45 transition-colors duration-200 hover:text-brand dark:text-white/35 dark:hover:text-brand rtl:font-arabic';
    $or = 'my-[22px] flex items-center gap-3 text-[12px] font-semibold tracking-[0.06em] text-black/30 uppercase before:h-px before:flex-1 before:bg-black/10 after:h-px after:flex-1 after:bg-black/10 dark:text-white/25 dark:before:bg-white/8 dark:after:bg-white/8 rtl:font-arabic';
    $social = 'flex h-[46px] flex-1 cursor-pointer items-center justify-center rounded-[10px] border-[1.5px] border-[#e0ddd8] bg-white transition-[border-color,background,translate] duration-200 hover:-translate-y-0.5 hover:border-brand hover:bg-[rgba(200,16,46,0.04)] dark:border-white/10 dark:bg-[#1f1f1f] dark:hover:border-brand dark:hover:bg-[rgba(200,16,46,0.08)] [&_svg]:size-5';
    $error = 'mt-1.5 block text-[12px] text-danger rtl:font-arabic';
    $terms = 'mt-[18px] text-center text-[11px] leading-[1.6] text-black/38 dark:text-white/28 rtl:font-arabic [&_a]:text-brand [&_a]:underline [&_a]:underline-offset-2';
    $backIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-[14px] rtl:-scale-x-100" aria-hidden="true"><polyline points="15 18 9 12 15 6" /></svg>';
    $google = '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>';
    $facebook = '<svg viewBox="0 0 24 24" fill="#1877F2" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>';
@endphp

<div
    x-data="{
        open: false,
        step: 'phone',
        countries: @js($countries),
        country: 0,
        phone: '',
        dial: false,
        showPassword: false,
        soon: '',
        welcome: '',
        soonTimer: null,
        resendIn: 0,
        resendTimer: null,
        show() {
            this.open = true
            this.go('phone')
            document.body.style.overflow = 'hidden'
            this.$nextTick(() => setTimeout(() => this.$refs.phone?.focus(), 60))
        },
        close() {
            this.open = false
            this.dial = false
            clearInterval(this.resendTimer)
            document.body.style.overflow = ''
        },
        go(step) {
            this.step = step
            this.showPassword = false
            this.soon = ''
        },
        comingSoon(provider) {
            clearTimeout(this.soonTimer)
            this.soon = provider
            this.soonTimer = setTimeout(() => (this.soon = ''), 2500)
        },
        startResendTimer() {
            this.resendIn = 30
            clearInterval(this.resendTimer)
            this.resendTimer = setInterval(() => --this.resendIn <= 0 && clearInterval(this.resendTimer), 1000)
        },
    }"
    @open-auth.window="show()"
    @signed-in.window="welcome = $event.detail.name; go('success'); setTimeout(() => window.location.reload(), 1600)"
    @keydown.escape.window="open && close()"
    @click.self="close()"
    x-show="open"
    x-transition:enter="transition-opacity duration-280 ease-out"
    x-transition:enter-start="opacity-0"
    x-transition:leave="transition-opacity duration-280 ease-out"
    x-transition:leave-end="opacity-0"
    x-cloak
    style="display: none"
    class="fixed inset-0 z-9999 flex items-center justify-center bg-[rgba(8,8,8,0.65)] p-4 backdrop-blur-[6px] max-[620px]:overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="auth-title"
>
    <div
        x-show="open"
        x-transition:enter="transition-transform duration-320 ease-out-ex"
        x-transition:enter-start="translate-y-[18px] scale-97"
        x-transition:enter-end="translate-y-0 scale-100"
        class="relative flex w-full max-w-[750px] rounded-[18px] border border-[#4d4d4d] dark:border-white/10 max-[620px]:m-auto max-[620px]:max-h-[calc(100dvh-32px)] max-[620px]:overflow-x-hidden max-[620px]:overflow-y-auto max-[620px]:rounded-[14px]"
    >
        <button type="button" @click="close()" class="absolute top-4 right-4 z-2 flex size-8 cursor-pointer items-center justify-center rounded-full border-none bg-black/7 text-[18px] leading-none text-[#555] transition-[background,color] duration-200 hover:bg-[rgba(200,16,46,0.1)] hover:text-brand dark:bg-white/7 dark:text-white/60 dark:hover:bg-[rgba(200,16,46,0.15)] dark:hover:text-brand rtl:right-auto rtl:left-4" aria-label="{{ __('Close') }}">&times;</button>

        {{-- Left: the benefits (not on phones) --}}
        <div class="relative flex w-[280px] flex-none flex-col overflow-hidden rounded-s-[18px] bg-[linear-gradient(160deg,#111_0%,#1c1c1c_60%,#0d0d0d_100%)] px-8 pt-11 pb-10 before:pointer-events-none before:absolute before:-top-[60px] before:-left-[60px] before:size-[220px] before:rounded-full before:bg-[radial-gradient(circle,rgba(200,16,46,0.18)_0%,transparent_70%)] after:pointer-events-none after:absolute after:-right-10 after:-bottom-10 after:size-40 after:rounded-full after:bg-[radial-gradient(circle,rgba(200,16,46,0.1)_0%,transparent_70%)] max-[620px]:hidden" aria-hidden="true">
            <div class="relative mx-auto mb-7 size-[120px]">
                <div class="flex size-full items-center justify-center rounded-full border border-white/8 bg-white/5">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" class="size-[54px] text-white opacity-90"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" /><polyline points="9 22 9 12 15 12 15 22" /></svg>
                </div>
                <span class="absolute top-2 right-3 size-[10px] rounded-full bg-brand opacity-90"></span>
                <span class="absolute bottom-4 left-1 size-1.5 rounded-full bg-[rgba(200,16,46,0.6)] opacity-65"></span>
                <span class="absolute top-[30px] left-0 size-1 rounded-full bg-white/40"></span>
            </div>
            <h3 class="mb-4 text-[17px] leading-[1.3] font-bold tracking-[-0.01em] text-white rtl:font-arabic [&_span]:text-brand">{!! __('Keep track of<br><span>your dream home</span>') !!}</h3>
            <ul class="m-0 flex flex-1 list-none flex-col gap-[10px] p-0">
                @foreach ([__('Save and track favourite properties'), __('Be notified of any price change'), __('Unlock exclusive tools & insights')] as $benefit)
                    <li class="flex items-start gap-[9px] text-[12.5px] leading-[1.4] text-white/65 rtl:font-arabic"><span class="mt-1 size-1.5 flex-none rounded-full bg-brand"></span>{{ $benefit }}</li>
                @endforeach
            </ul>
        </div>

        {{-- Right: the steps --}}
        <div class="flex flex-1 flex-col rounded-e-[18px] bg-white px-10 pt-11 pb-9 dark:bg-[#161616] max-[620px]:rounded-[14px] max-[620px]:px-6 max-[620px]:pt-10 max-[620px]:pb-7 max-[420px]:px-[18px] max-[420px]:pt-9 max-[420px]:pb-6">
            <h2 id="auth-title" x-show="step !== 'success'" class="mb-7 text-[20px] font-bold tracking-[-0.02em] text-[#0d0d0d] dark:text-[#f0ede8] rtl:font-arabic">{{ __('Sign in or Register') }}</h2>

            {{-- Step: WhatsApp number --}}
            <div x-show="step === 'phone'" class="flex flex-1 flex-col">
                <label for="auth-phone" class="{{ $label }}">{{ __('WhatsApp number') }}</label>
                <div class="flex items-stretch gap-2 max-[420px]:flex-wrap">
                    <div class="relative" @click.outside="dial = false">
                        <button type="button" @click="dial = ! dial" :aria-expanded="dial" aria-haspopup="listbox" class="flex h-12 flex-none cursor-pointer items-center gap-[5px] rounded-[10px] border-[1.5px] border-[#e0ddd8] bg-white px-[10px] text-[13px] whitespace-nowrap text-[#0d0d0d] transition-[border-color] duration-200 hover:border-brand dark:border-white/10 dark:bg-[#1f1f1f] dark:text-[#f0ede8]" aria-label="{{ __('Select country code') }}">
                            <span class="text-[16px] leading-none" x-text="countries[country][1]"></span>
                            <span class="text-[13px] font-semibold" dir="ltr" x-text="countries[country][2]"></span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-3 text-black/40 dark:text-white/40" aria-hidden="true"><polyline points="6 9 12 15 18 9" /></svg>
                        </button>
                        <div x-show="dial" x-cloak class="absolute top-[calc(100%+6px)] left-0 z-10 min-w-[180px] overflow-hidden rounded-[10px] border border-[#e8e5e0] bg-white dark:border-white/10 dark:bg-[#222] rtl:right-0 rtl:left-auto" role="listbox">
                            <template x-for="(item, i) in countries" :key="item[0]">
                                <div @click="country = i; dial = false" role="option" :aria-selected="country === i" class="flex cursor-pointer items-center gap-2 px-[14px] py-[9px] text-[13px] text-[#0d0d0d] transition-[background] duration-150 hover:bg-[rgba(200,16,46,0.06)] dark:text-[#f0ede8] dark:hover:bg-[rgba(200,16,46,0.1)]" :class="country === i && 'bg-[rgba(200,16,46,0.08)] font-semibold'">
                                    <span x-text="item[1]"></span>
                                    <span x-text="item[3]"></span>
                                    <span class="ms-auto text-[11px] opacity-50" dir="ltr" x-text="item[2]"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                    <input id="auth-phone" x-ref="phone" x-model="phone" type="tel" inputmode="numeric" maxlength="14" autocomplete="tel-national" dir="ltr" placeholder="1XXXXXXXXX" class="{{ $input }} max-[420px]:basis-[120px] rtl:text-right" @keydown.enter="go('otp'); startResendTimer()" />
                    <button type="button" @click="go('otp'); startResendTimer()" class="{{ $goButton }} max-[420px]:basis-full">{{ __('Sign in') }}</button>
                </div>
                <div class="{{ $or }}">{{ __('OR') }}</div>
                <div class="flex gap-[10px] max-[620px]:gap-2">
                    <button type="button" @click="go('email')" class="{{ $social }}" title="{{ __('Continue with Email') }}" aria-label="{{ __('Continue with Email') }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="text-[#0d0d0d] dark:text-[#f0ede8]" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" /></svg>
                    </button>
                    <button type="button" @click="comingSoon('Google')" class="{{ $social }}" title="{{ __('Continue with Google') }}" aria-label="{{ __('Continue with Google') }}">{!! $google !!}</button>
                    <button type="button" @click="comingSoon('Facebook')" class="{{ $social }}" title="{{ __('Continue with Facebook') }}" aria-label="{{ __('Continue with Facebook') }}">{!! $facebook !!}</button>
                </div>
                <p x-show="soon" x-cloak class="mt-3 text-center text-[12px] text-black/50 dark:text-white/45 rtl:font-arabic" role="status" x-text="@js(__(':provider sign-in is coming soon.', ['provider' => '__P__'])).replace('__P__', soon)"></p>
                <p class="{{ $terms }}">{!! __('By registering you accept our <a href=":terms">Terms & Conditions</a> and <a href=":privacy">our privacy policy</a>.', ['terms' => route('terms'), 'privacy' => route('privacy')]) !!}</p>
            </div>

            {{-- Step: the 6-digit code --}}
            <div x-show="step === 'otp'" x-cloak class="flex flex-1 flex-col">
                <button type="button" @click="clearInterval(resendTimer); go('phone')" class="{{ $back }}">{!! $backIcon !!}{{ __('Back') }}</button>
                <p class="mb-[22px] text-[13.5px] leading-[1.5] text-black/55 dark:text-white/50 rtl:font-arabic [&_strong]:text-[#0d0d0d] dark:[&_strong]:text-[#f0ede8]">
                    {{ __('Enter the 6-digit code sent to your WhatsApp at') }}
                    <strong dir="ltr" x-text="countries[country][2] + ' ' + phone"></strong>
                </p>
                <div
                    x-data="{
                        digits: ['', '', '', '', '', ''],
                        type(i, value) {
                            this.digits[i] = value.replace(/\D/g, '').slice(-1)
                            if (this.digits[i] && i < 5) this.$refs['box' + (i + 1)].focus()
                        },
                        erase(i) {
                            if (this.digits[i] || i === 0) return
                            this.digits[i - 1] = ''
                            this.$refs['box' + (i - 1)].focus()
                        },
                        paste(i, text) {
                            text.replace(/\D/g, '').slice(0, 6 - i).split('').forEach((d, j) => (this.digits[i + j] = d))
                            this.$refs['box' + Math.min(5, i + text.replace(/\D/g, '').length)]?.focus()
                        },
                    }"
                    class="mb-[22px] flex gap-[10px] max-[620px]:gap-[7px]"
                    dir="ltr"
                >
                    @foreach (range(0, 5) as $i)
                        <input
                            x-ref="box{{ $i }}"
                            :value="digits[{{ $i }}]"
                            @input="type({{ $i }}, $event.target.value); $event.target.value = digits[{{ $i }}]"
                            @keydown.backspace="erase({{ $i }})"
                            @paste.prevent="paste({{ $i }}, $event.clipboardData.getData('text'))"
                            maxlength="1"
                            inputmode="numeric"
                            @if ($i === 0) autocomplete="one-time-code" @endif
                            class="h-[52px] w-full min-w-0 flex-1 rounded-[10px] border-[1.5px] border-[#e0ddd8] bg-white text-center font-[inherit] text-[22px] font-bold text-[#0d0d0d] caret-brand outline-none transition-[border-color] duration-200 focus:border-brand dark:border-white/10 dark:bg-[#1f1f1f] dark:text-[#f0ede8] max-[620px]:h-[46px] max-[620px]:text-[18px]"
                            :class="digits[{{ $i }}] && 'border-brand! bg-[rgba(200,16,46,0.04)] dark:bg-[rgba(200,16,46,0.08)]'"
                            aria-label="{{ __('Digit :number', ['number' => $i + 1]) }}"
                        />
                    @endforeach
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <button type="button" @click="startResendTimer()" :disabled="resendIn > 0" class="cursor-pointer border-none bg-none p-0 text-[12.5px] text-black/40 transition-colors duration-200 enabled:hover:text-brand disabled:cursor-default disabled:opacity-45 dark:text-white/30 rtl:font-arabic" x-text="resendIn > 0 ? @js(__('Resend code (:seconds s)', ['seconds' => '__S__'])).replace('__S__', resendIn) : @js(__('Resend code'))"></button>
                    <button type="button" class="h-11 cursor-pointer rounded-[10px] border-none bg-brand px-7 text-[13.5px] font-bold text-white transition-[background] duration-200 hover:bg-[#a50d26] rtl:font-arabic">{{ __('Verify') }}</button>
                </div>
            </div>

            {{-- Step: e-mail and password. A <form> so the browser fills a saved e-mail and password here only (fields
                 outside any form are treated as one form, and the e-mail went into other pages' search boxes). --}}
            <form x-show="step === 'email'" x-cloak @submit.prevent class="flex flex-1 flex-col" novalidate>
                <button type="button" @click="go('phone'); $wire.registering && $wire.cancelRegistration()" class="{{ $back }}">{!! $backIcon !!}{{ __('Back') }}</button>
                @if ($registering)
                    {{-- A new e-mail: the same step creates the account. --}}
                    <p class="mb-4 text-[13px] leading-[1.5] text-black/55 dark:text-white/50 rtl:font-arabic">{{ __("No account with this e-mail yet: add your name to create one.") }}</p>
                    <label for="auth-name" class="{{ $label }}">{{ __('Full name') }}</label>
                    <div class="mb-3 flex flex-col">
                        <input id="auth-name" type="text" wire:model="name" autocomplete="name" class="{{ $input }} w-full flex-none" />
                        @error('name') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>
                @endif
                <label for="auth-email" class="{{ $label }}">{{ __('Email') }}</label>
                <div class="mb-3 flex flex-col">
                    <input id="auth-email" type="email" wire:model="email" @input="$wire.registering && $wire.cancelRegistration()" autocomplete="email" placeholder="you@example.com" class="{{ $input }} w-full flex-none" />
                    @error('email') <span class="{{ $error }}">{{ $message }}</span> @enderror
                </div>
                <label for="auth-password" class="{{ $label }}">{{ __('Password') }}</label>
                <div class="mb-3 flex gap-2">
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
                    <div class="mb-3 flex flex-col">
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
                <p class="{{ $terms }}">{!! __('By registering you accept our <a href=":terms">Terms & Conditions</a> and <a href=":privacy">our privacy policy</a>.', ['terms' => route('terms'), 'privacy' => route('privacy')]) !!}</p>
            </form>

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
                    <p class="mb-4 text-[13px] leading-[1.5] text-black/55 dark:text-white/50 rtl:font-arabic">{{ __("Enter your e-mail and we'll send you a link to choose a new password.") }}</p>
                    <label for="auth-reset-email" class="{{ $label }}">{{ __('Email') }}</label>
                    <div class="mb-3 flex flex-col">
                        <input id="auth-reset-email" type="email" wire:model="email" @keydown.enter="$wire.sendResetLink()" autocomplete="email" placeholder="you@example.com" class="{{ $input }} w-full flex-none" />
                        @error('email') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>
                    <button type="button" wire:click="sendResetLink" wire:loading.attr="disabled" class="{{ $goButton }} w-full disabled:opacity-70">{{ __('Send link') }}</button>
                @endif
            </div>

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
        </div>
    </div>
</div>
