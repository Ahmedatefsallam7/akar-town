{{--
    "Sign in or Register" (App\Livewire\Website\AuthModal), as the design's auth-modal: a dark panel with the benefits
    (hidden on phones) and the steps: WhatsApp number, 6-digit code, e-mail and password, welcome. Opened by the
    window event "open-auth"; Escape, the × and a click outside close it. Google / Facebook (AKAR-55) link to the
    provider when their keys are set, otherwise say "coming soon" (auth-modal/provider). WhatsApp (AKAR-49): the number's password, or a code then "choose a password"; after 3
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

        @include('livewire.website.auth-modal.benefits')

        {{-- Right: the steps --}}
        <div @scroll.passive="dial && place()" class="flex min-h-0 flex-1 flex-col overflow-y-auto rounded-e-[18px] bg-white px-10 pt-9 pb-8 [@media(max-height:780px)]:pt-6 [@media(max-height:780px)]:pb-5 [scrollbar-width:thin] dark:bg-[#161616] max-[620px]:rounded-[14px] max-[620px]:px-6 max-[620px]:pt-10 max-[620px]:pb-7 max-[420px]:px-[18px] max-[420px]:pt-9 max-[420px]:pb-6">
            <h2 id="auth-title" x-show="step !== 'success'" class="mb-5 [@media(max-height:780px)]:mb-3 text-[20px] font-bold tracking-[-0.02em] text-[#0d0d0d] dark:text-[#f0ede8] rtl:font-arabic">{{ __('Sign in or Register') }}</h2>

            @include('livewire.website.auth-modal.phone')

            @include('livewire.website.auth-modal.country-picker')

            @include('livewire.website.auth-modal.code')

            @include('livewire.website.auth-modal.phone-password')

            @include('livewire.website.auth-modal.phone-setup')

            @include('livewire.website.auth-modal.undelivered')

            @include('livewire.website.auth-modal.locked')

            @include('livewire.website.auth-modal.email')

            @include('livewire.website.auth-modal.forgot')

            @include('livewire.website.auth-modal.welcome')
        </div>
    </div>
</div>
