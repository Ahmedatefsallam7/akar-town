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
