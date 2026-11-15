{{--
    "Sign in or Register" (App\Livewire\Website\AuthModal), as the design's auth-modal: a dark panel with the benefits
    (hidden on phones) and the steps: WhatsApp number, 6-digit code, e-mail and password, welcome. Opened by the
    window event "open-auth"; Escape, the × and a click outside close it. Google / Facebook aren't connected yet:
    they say "coming soon". WhatsApp (AKAR-49): the number's password, or a code then "choose a password"; after 3
    wrong codes the number is locked and the window offers to contact us or another way in.
--}}
@php
    $countries = \App\Livewire\Website\AuthModal::COUNTRIES;
    $business = app(\Modules\GeneralSettings\Settings\BusinessInfoSettings::class);
    $note = 'mb-4 [@media(max-height:780px)]:mb-2.5 text-[13px] leading-[1.5] text-black/55 dark:text-white/50 rtl:font-arabic [&_strong]:text-[#0d0d0d] dark:[&_strong]:text-[#f0ede8]';
    $link = 'cursor-pointer border-none bg-none p-0 text-[12px] text-black/45 underline-offset-2 transition-colors duration-200 hover:text-brand hover:underline dark:text-white/40 rtl:font-arabic';
    $label = 'mb-1.5 block [@media(max-height:780px)]:mb-1 text-[12.5px] font-semibold tracking-[0.01em] text-[#0d0d0d] dark:text-[#d8d4ce] rtl:font-arabic';
    $input = 'h-11 [@media(max-height:780px)]:h-10 min-w-0 flex-1 rounded-[10px] border-[1.5px] border-[#e0ddd8] bg-white px-[14px] font-[inherit] text-[14px] text-[#0d0d0d] outline-none transition-[border-color] duration-200 placeholder:text-black/28 focus:border-brand dark:border-white/10 dark:bg-[#1f1f1f] dark:text-[#f0ede8] dark:placeholder:text-white/20';
    $goButton = 'h-11 [@media(max-height:780px)]:h-10 flex-none cursor-pointer rounded-[10px] border-none bg-brand px-[22px] text-[14px] font-bold tracking-[0.02em] whitespace-nowrap text-white transition-[background,translate] duration-200 hover:-translate-y-px hover:bg-[#a50d26] active:translate-y-0 max-[420px]:px-4 rtl:font-arabic';
    $back = 'mb-[18px] [@media(max-height:780px)]:mb-2.5 inline-flex cursor-pointer items-center gap-1.5 self-start border-none bg-none p-0 text-[12px] text-black/45 transition-colors duration-200 hover:text-brand dark:text-white/35 dark:hover:text-brand rtl:font-arabic';
    $or = 'my-4 [@media(max-height:780px)]:my-2 flex items-center gap-3 text-[12px] font-semibold tracking-[0.06em] text-black/30 uppercase before:h-px before:flex-1 before:bg-black/10 after:h-px after:flex-1 after:bg-black/10 dark:text-white/25 dark:before:bg-white/8 dark:after:bg-white/8 rtl:font-arabic';
    $social = 'flex h-[46px] [@media(max-height:780px)]:h-10 flex-1 cursor-pointer items-center justify-center rounded-[10px] border-[1.5px] border-[#e0ddd8] bg-white transition-[border-color,background,translate] duration-200 hover:-translate-y-0.5 hover:border-brand hover:bg-[rgba(200,16,46,0.04)] dark:border-white/10 dark:bg-[#1f1f1f] dark:hover:border-brand dark:hover:bg-[rgba(200,16,46,0.08)] [&_svg]:size-5';
    $error = 'mt-1.5 block text-[12px] text-danger rtl:font-arabic';
    $terms = 'mt-[18px] [@media(max-height:780px)]:mt-2.5 text-center text-[11px] leading-[1.6] text-black/38 dark:text-white/28 rtl:font-arabic [&_a]:text-brand [&_a]:underline [&_a]:underline-offset-2';
    $backIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-[14px] rtl:-scale-x-100" aria-hidden="true"><polyline points="15 18 9 12 15 6" /></svg>';
    $google = '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>';
    $facebook = '<svg viewBox="0 0 24 24" fill="#1877F2" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>';
@endphp

<div
    x-data="{
        open: false,
        step: 'phone',
        countries: @js(collect($countries)->map(fn (array $item) => [$item[0], $item[1], $item[2], __($item[3])])->all()),
        country: 0,
        dial: false,
        find: '',
        active: 0,
        pos: {},
        // The country picker: opened under the number field, or above it when there's no room below (fixed to the
        // screen, so the window never cuts it off); a search box, arrow keys and Enter to choose.
        openDial() {
            this.find = ''
            this.place()
            this.dial = true
            this.active = Math.max(0, this.found().findIndex(item => item[4] === this.country))
            this.$nextTick(() => this.$refs.find?.focus())
        },
        closeDial(back = true) {
            this.dial = false
            if (back) this.$nextTick(() => this.$refs.phone?.focus())
        },
        place() {
            const box = this.$refs.field.getBoundingClientRect()
            const below = innerHeight - box.bottom - 12
            const above = box.top - 12
            const up = below < 260 && above > below
            this.pos = {
                left: box.left + 'px',
                width: box.width + 'px',
                top: up ? 'auto' : box.bottom + 6 + 'px',
                bottom: up ? innerHeight - box.top + 6 + 'px' : 'auto',
                maxHeight: Math.min(340, up ? above : below) + 'px',
            }
        },
        found() {
            const text = this.find.trim().toLowerCase().replace(/^\+|^00/, '')
            return this.countries
                .map((item, i) => [...item, i])
                .filter(item => ! text || item[3].toLowerCase().includes(text) || item[0].toLowerCase().startsWith(text) || item[2].slice(1).startsWith(text))
        },
        pick(item) {
            this.country = item[4]
            $wire.set('country', item[0])
            this.closeDial()
        },
        step(by) {
            const list = this.found()
            if (! list.length) return
            this.active = (this.active + by + list.length) % list.length
            this.$nextTick(() => this.$refs.options?.children[this.active]?.scrollIntoView({ block: 'nearest' }))
        },
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
        startResendTimer(seconds = 60) {
            this.resendIn = seconds
            clearInterval(this.resendTimer)
            this.resendTimer = setInterval(() => --this.resendIn <= 0 && clearInterval(this.resendTimer), 1000)
        },
    }"
    @open-auth.window="show()"
    x-effect="country = Math.max(0, countries.findIndex(item => item[0] === $wire.country))"
    x-on:auth-step.window="go($event.detail.step); $event.detail.resendIn && startResendTimer($event.detail.resendIn)"
    @signed-in.window="welcome = $event.detail.name; go('success'); setTimeout(() => window.location.reload(), 1600)"
    @keydown.escape.window="open && close()"
    @click.self="close()"
    x-show="open"
    x-transition:enter="transition-opacity duration-200 ease-out"
    x-transition:enter-start="opacity-0"
    x-transition:leave="transition-opacity duration-200 ease-out"
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
        x-transition:enter="transition-transform duration-220 ease-out-ex"
        x-transition:enter-start="translate-y-[18px] scale-97"
        x-transition:enter-end="translate-y-0 scale-100"
        class="relative flex max-h-[calc(100dvh-32px)] w-full max-w-[750px] rounded-[18px] border border-[#4d4d4d] dark:border-white/10 max-[620px]:m-auto max-[620px]:overflow-x-hidden max-[620px]:overflow-y-auto max-[620px]:rounded-[14px]"
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
        <div @scroll.passive="dial && place()" class="flex min-h-0 flex-1 flex-col overflow-y-auto rounded-e-[18px] bg-white px-10 pt-9 pb-8 [@media(max-height:780px)]:pt-6 [@media(max-height:780px)]:pb-5 [scrollbar-width:thin] dark:bg-[#161616] max-[620px]:rounded-[14px] max-[620px]:px-6 max-[620px]:pt-10 max-[620px]:pb-7 max-[420px]:px-[18px] max-[420px]:pt-9 max-[420px]:pb-6">
            <h2 id="auth-title" x-show="step !== 'success'" class="mb-5 [@media(max-height:780px)]:mb-3 text-[20px] font-bold tracking-[-0.02em] text-[#0d0d0d] dark:text-[#f0ede8] rtl:font-arabic">{{ __('Sign in or Register') }}</h2>

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

            {{-- The country picker (see openDial): on the page itself, above the window, never cut off. --}}
            @teleport('body')
                <div x-show="open && dial" x-cloak
                    x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="-translate-y-1 opacity-0"
                    x-transition:leave="transition duration-100 ease-in" x-transition:leave-end="opacity-0"
                    :style="pos"
                    @click.outside="dial && ! $refs.field.contains($event.target) && closeDial(false)"
                    @resize.window="dial && place()"
                    class="fixed z-[10000] flex flex-col overflow-hidden rounded-[14px] border border-[#e8e5e0] bg-white shadow-[0_18px_44px_-14px_rgba(0,0,0,0.35)] dark:border-white/10 dark:bg-[#1c1c1c]"
                    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
                    <div class="flex flex-none items-center gap-2 border-b border-black/7 px-3 dark:border-white/8">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4 flex-none text-black/35 dark:text-white/35" aria-hidden="true"><circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" /></svg>
                        <input x-ref="find" x-model="find" type="search" autocomplete="off" placeholder="{{ __('Search a country or code') }}" aria-label="{{ __('Search a country or code') }}"
                            @input="active = 0"
                            @keydown.down.prevent="step(1)" @keydown.up.prevent="step(-1)"
                            @keydown.enter.prevent="found()[active] && pick(found()[active])"
                            @keydown.escape.stop.prevent="closeDial()" @keydown.tab="closeDial(false)"
                            class="h-11 min-w-0 flex-1 bg-transparent text-[13.5px] text-[#0d0d0d] outline-none placeholder:text-black/35 rtl:font-arabic dark:text-[#f0ede8] dark:placeholder:text-white/30 [&::-webkit-search-cancel-button]:hidden" />
                    </div>
                    <div x-ref="options" class="min-h-0 flex-1 overflow-y-auto py-1" role="listbox" aria-label="{{ __('Choose your country') }}">
                        <template x-for="(item, i) in found()" :key="item[0]">
                            <button type="button" role="option" :aria-selected="country === item[4]"
                                @click="pick(item)" @mouseenter="active = i"
                                class="flex w-full cursor-pointer items-center gap-3 px-3.5 py-2.5 text-start transition-[background] duration-100"
                                :class="active === i ? 'bg-[rgba(200,16,46,0.06)] dark:bg-[rgba(200,16,46,0.12)]' : ''">
                                <span class="text-[20px] leading-none" x-text="item[1]"></span>
                                <span class="min-w-0 flex-1 truncate text-[13.5px] text-[#0d0d0d] rtl:font-arabic dark:text-[#f0ede8]" :class="country === item[4] && 'font-semibold'" x-text="item[3]"></span>
                                <bdi dir="ltr" class="text-[12.5px] text-black/45 tabular-nums dark:text-white/40" x-text="item[2]"></bdi>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="size-4 flex-none text-brand" :class="country === item[4] ? 'opacity-100' : 'opacity-0'" aria-hidden="true"><polyline points="20 6 9 17 4 12" /></svg>
                            </button>
                        </template>
                        <p x-show="! found().length" class="px-3.5 py-4 text-center text-[13px] text-black/45 rtl:font-arabic dark:text-white/40">{{ __('No country found') }}</p>
                    </div>
                </div>
            @endteleport

            {{-- Step: the 6-digit code (it opens on its own once the six digits are typed) --}}
            <div x-show="step === 'otp'" x-cloak class="flex flex-1 flex-col">
                <button type="button" @click="clearInterval(resendTimer); go('phone')" class="{{ $back }}">{!! $backIcon !!}{{ __('Back') }}</button>
                <p class="mb-[22px] [@media(max-height:780px)]:mb-3 text-[13.5px] leading-[1.5] text-black/55 dark:text-white/50 rtl:font-arabic [&_strong]:text-[#0d0d0d] dark:[&_strong]:text-[#f0ede8]">
                    {{ __('Enter the 6-digit code sent to your WhatsApp at') }}
                    <strong><bdi dir="ltr">{{ $e164 }}</bdi></strong>
                </p>
                {{-- Whether the code arrived (Meta's webhook), asked every few seconds for a short while. --}}
                @if ($delivery === 'delivered')
                    <p class="-mt-3 mb-4 flex items-center gap-1.5 text-[12.5px] font-semibold text-[#128c4a] dark:text-[#4ade80] rtl:font-arabic" role="status">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-[14px] flex-none" aria-hidden="true"><polyline points="20 6 9 17 4 12" /></svg>
                        {{ __('Delivered on WhatsApp') }}
                    </p>
                @elseif ($this->following())
                    <p wire:poll.3s="checkDelivery" class="-mt-3 mb-4 flex items-center gap-1.5 text-[12.5px] text-black/45 dark:text-white/40 rtl:font-arabic" role="status">
                        <span class="size-3 flex-none animate-spin rounded-full border-2 border-black/15 border-t-brand dark:border-white/15" aria-hidden="true"></span>
                        {{ __('Sending to WhatsApp…') }}
                    </p>
                @endif
                <div
                    x-data="{
                        digits: ['', '', '', '', '', ''],
                        type(i, value) {
                            this.digits[i] = value.replace(/\D/g, '').slice(-1)
                            if (this.digits[i] && i < 5) this.$refs['box' + (i + 1)].focus()
                            this.check()
                        },
                        erase(i) {
                            if (this.digits[i] || i === 0) return
                            this.digits[i - 1] = ''
                            this.$refs['box' + (i - 1)].focus()
                        },
                        paste(i, text) {
                            text.replace(/\D/g, '').slice(0, 6 - i).split('').forEach((d, j) => (this.digits[i + j] = d))
                            this.$refs['box' + Math.min(5, i + text.replace(/\D/g, '').length)]?.focus()
                            this.check()
                        },
                        check() {
                            if (this.digits.every(Boolean)) $wire.verifyCode(this.digits.join(''))
                        },
                    }"
                    @code-wrong.window="digits = ['', '', '', '', '', '']; $nextTick(() => $refs.box0.focus())"
                    @verify-code.window="$wire.verifyCode(digits.join(''))"
                    x-on:auth-step.window="$event.detail.step === 'otp' && (digits = ['', '', '', '', '', ''], setTimeout(() => $refs.box0.focus(), 60))"
                    class="mb-3 flex gap-[10px] max-[620px]:gap-[7px]"
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
                            @class([
                                'h-[52px] w-full min-w-0 flex-1 rounded-[10px] border-[1.5px] bg-white text-center font-[inherit] text-[22px] font-bold text-[#0d0d0d] caret-brand outline-none transition-[border-color] duration-200 focus:border-brand dark:bg-[#1f1f1f] dark:text-[#f0ede8] max-[620px]:h-[46px] max-[620px]:text-[18px]',
                                'border-danger' => $errors->has('code'),
                                'border-[#e0ddd8] dark:border-white/10' => ! $errors->has('code'),
                            ])
                            :class="digits[{{ $i }}] && 'border-brand! bg-[rgba(200,16,46,0.04)] dark:bg-[rgba(200,16,46,0.08)]'"
                            aria-label="{{ __('Digit :number', ['number' => $i + 1]) }}"
                        />
                    @endforeach
                </div>
                <div class="mb-[18px] min-h-[18px] [@media(max-height:780px)]:mb-3" aria-live="polite">
                    @if ($errors->has('code'))
                        <span class="flex items-center gap-1.5 text-[12.5px] text-danger rtl:font-arabic" role="alert">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-[14px] flex-none" aria-hidden="true"><circle cx="12" cy="12" r="10" /><line x1="12" y1="8" x2="12" y2="12" /><line x1="12" y1="16" x2="12.01" y2="16" /></svg>
                            {{ $errors->first('code') }}
                        </span>
                    @else
                        <span class="text-[12px] text-black/40 dark:text-white/35 rtl:font-arabic">{{ trans_choice(':count attempt to enter the code.|:count attempts to enter the code.', $attemptsLeft, ['count' => $attemptsLeft]) }}</span>
                    @endif
                    @error('phone') <span class="{{ $error }}" role="alert">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <button type="button" wire:click="sendCode" :disabled="resendIn > 0" class="cursor-pointer border-none bg-none p-0 text-[12.5px] text-black/40 transition-colors duration-200 enabled:hover:text-brand disabled:cursor-default disabled:opacity-45 dark:text-white/30 rtl:font-arabic" x-text="resendIn > 0 ? @js(__('Resend code (:seconds s)', ['seconds' => '__S__'])).replace('__S__', resendIn) : @js(__('Resend code'))"></button>
                    <button type="button" @click="$dispatch('verify-code')" wire:loading.attr="disabled" wire:target="verifyCode" class="h-11 cursor-pointer rounded-[10px] border-none bg-brand px-7 text-[13.5px] font-bold text-white transition-[background] duration-200 hover:bg-[#a50d26] disabled:opacity-70 rtl:font-arabic">
                        <span wire:loading.remove wire:target="verifyCode">{{ __('Verify') }}</span>
                        <span wire:loading wire:target="verifyCode">{{ __('Checking…') }}</span>
                    </button>
                </div>
            </div>

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
                    <button type="button" @click="comingSoon('Google')" class="{{ $social }}" title="{{ __('Continue with Google') }}" aria-label="{{ __('Continue with Google') }}">{!! $google !!}</button>
                    <button type="button" @click="comingSoon('Facebook')" class="{{ $social }}" title="{{ __('Continue with Facebook') }}" aria-label="{{ __('Continue with Facebook') }}">{!! $facebook !!}</button>
                </div>
                <p x-show="soon" x-cloak class="mt-3 text-center text-[12px] text-black/50 dark:text-white/45 rtl:font-arabic" role="status" x-text="@js(__(':provider sign-in is coming soon.', ['provider' => '__P__'])).replace('__P__', soon)"></p>
            </div>

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
