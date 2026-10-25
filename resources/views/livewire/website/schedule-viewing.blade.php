{{--
    The property page's booking window (App\Livewire\Website\ScheduleViewing), in the Submit Property window's look:
    name, phone, e-mail (optional), meeting type, preferred date & time and a message. Opened by the window event
    "schedule-viewing"; Escape, the × and a click outside close it.
--}}
@php
    $label = 'text-[11px] font-semibold tracking-[0.08em] text-black/50 uppercase rtl:font-arabic rtl:tracking-normal dark:text-[rgba(237,232,223,0.4)]';
    $input = 'w-full rounded-[10px] border-[1.5px] border-black/10 bg-white/70 px-[14px] py-[11px] font-sans text-[13.5px] font-medium text-ink outline-none transition-[border-color,box-shadow] duration-200 placeholder:text-black/28 focus:border-brand focus:shadow-[0_0_0_3px_rgba(200,16,46,0.08)] dark:border-white/10 dark:bg-white/5 dark:text-[#ede8df] dark:placeholder:text-[rgba(237,232,223,0.2)] dark:[color-scheme:dark] dark:focus:shadow-[0_0_0_3px_rgba(200,16,46,0.12)]';
    $error = 'block text-[12px] text-danger rtl:font-arabic';
    $field = 'flex flex-col gap-1.5';
    $types = ['site_visit' => __('Site Visit'), 'in_person' => __('Office'), 'online' => __('Online'), 'call' => __('Call')];
    $optional = '<span class="font-normal tracking-normal normal-case opacity-60">('.e(__('optional')).')</span>';
@endphp

<div
    x-data="{
        open: false,
        show() {
            this.open = true
            document.body.style.overflow = 'hidden'
        },
        close() {
            this.open = false
            document.body.style.overflow = ''
        },
    }"
    @schedule-viewing.window="show()"
    @keydown.escape.window="open && close()"
    x-show="open"
    x-cloak
    x-transition:enter="transition-opacity duration-280 ease-out"
    x-transition:enter-start="opacity-0"
    x-transition:leave="transition-opacity duration-280 ease-out"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-9998 flex items-center justify-center bg-[rgba(8,8,8,0.65)] p-4 backdrop-blur-[6px]"
    @click.self="close()"
    role="dialog"
    aria-modal="true"
    aria-labelledby="sv-title"
>
    <div
        x-show="open"
        x-transition:enter="transition-transform duration-320 ease-out-ex"
        x-transition:enter-start="[transform:translateY(20px)_scale(0.97)]"
        class="relative flex max-h-[90vh] w-full max-w-[560px] flex-col overflow-hidden rounded-[20px] border border-black/8 bg-page shadow-[0_32px_80px_rgba(0,0,0,0.18)] dark:border-white/8 dark:bg-[#1a1814]"
    >
        <button type="button" @click="close()" class="absolute top-[14px] right-[14px] z-2 flex size-8 cursor-pointer items-center justify-center rounded-full border border-black/10 text-ink transition-[background] duration-200 hover:bg-black/6 rtl:right-auto rtl:left-[14px] dark:border-white/10 dark:text-[rgba(237,232,223,0.8)] dark:hover:bg-white/6" aria-label="{{ __('Close') }}">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
        </button>

        <div class="shrink-0 px-7 pt-7 max-[480px]:px-5 max-[480px]:pt-[22px]">
            <div class="mb-[14px] flex size-11 items-center justify-center rounded-xl bg-[rgba(200,16,46,0.08)] text-brand">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" /></svg>
            </div>
            <h2 id="sv-title" class="mb-1 text-[20px] font-bold text-ink rtl:font-arabic dark:text-[#ede8df]">{{ __('Schedule a Viewing') }}</h2>
            <p class="mb-[22px] text-[13px] text-black/50 rtl:font-arabic dark:text-[rgba(237,232,223,0.45)]">{{ __('Pick a time that suits you and our team will confirm it.') }}</p>
        </div>
        <div class="mx-7 h-px bg-black/7 max-[480px]:mx-5 dark:bg-white/7"></div>

        <div class="flex-1 overflow-y-auto px-7 pt-5 pb-7 max-[480px]:px-5 max-[480px]:pt-4 max-[480px]:pb-5 [&::-webkit-scrollbar]:w-1 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-black/15 dark:[&::-webkit-scrollbar-thumb]:bg-white/15">
            @if ($sent)
                <div class="flex flex-col items-center gap-[14px] px-7 pt-10 pb-8 text-center" role="status">
                    <div class="flex size-[76px] animate-pop items-center justify-center rounded-full bg-[rgba(25,160,70,0.1)] text-[#19a046]">
                        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12" /></svg>
                    </div>
                    <h3 class="mt-1 text-[22px] font-bold text-ink rtl:font-arabic dark:text-[#ede8df]">{{ __('Viewing Requested!') }}</h3>
                    <p class="text-[14px] leading-[1.65] text-black/50 rtl:font-arabic dark:text-[rgba(237,232,223,0.5)]">{{ __('Our team will contact you shortly to confirm the details.') }}</p>
                    <button type="button" wire:click="again" class="mt-2 cursor-pointer rounded-full border-[1.5px] border-black/12 px-7 py-[11px] text-[13px] font-semibold text-ink transition-[border-color,color] duration-200 hover:border-brand hover:text-brand rtl:font-arabic dark:border-[rgba(237,232,223,0.15)] dark:text-[rgba(237,232,223,0.8)]">{{ __('Book Another Time') }}</button>
                </div>
            @else
                <form wire:submit="send" class="flex flex-col gap-4" novalidate>
                    <div class="grid grid-cols-[repeat(2,minmax(0,1fr))] gap-3 max-[480px]:grid-cols-1">
                        <div class="{{ $field }}">
                            <label for="sv-name" class="{{ $label }}">{{ __('Full Name') }}</label>
                            <input id="sv-name" type="text" wire:model="fullName" autocomplete="name" class="{{ $input }} rtl:font-arabic" placeholder="{{ __('Your full name') }}" />
                            @error('fullName') <span class="{{ $error }}">{{ $message }}</span> @enderror
                        </div>
                        <div class="{{ $field }}">
                            <label for="sv-phone" class="{{ $label }}">{{ __('Phone Number') }}</label>
                            <input id="sv-phone" type="tel" wire:model="phone" autocomplete="tel" dir="ltr" class="{{ $input }} rtl:text-right" placeholder="+20 1XX XXX XXXX" />
                            @error('phone') <span class="{{ $error }}">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="{{ $field }}">
                        <label for="sv-email" class="{{ $label }}">{{ __('Email Address') }} {!! $optional !!}</label>
                        <input id="sv-email" type="email" wire:model="email" autocomplete="email" class="{{ $input }}" placeholder="you@example.com" />
                        @error('email') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>

                    <div class="{{ $field }}">
                        <span id="sv-type" class="{{ $label }}">{{ __('Meeting Type') }}</span>
                        <div class="grid grid-cols-4 gap-2 max-[480px]:grid-cols-2" role="radiogroup" aria-labelledby="sv-type">
                            @foreach ($types as $value => $name)
                                <button
                                    type="button"
                                    role="radio"
                                    :aria-checked="$wire.type === @js($value)"
                                    @click="$wire.type = @js($value)"
                                    class="cursor-pointer rounded-[10px] border-[1.5px] px-2 py-[11px] text-[12.5px] font-semibold transition-[background,border-color,color] duration-200 rtl:font-arabic"
                                    :class="$wire.type === @js($value) ? 'border-brand bg-brand text-white' : 'border-black/10 text-black/55 hover:border-black/25 dark:border-white/10 dark:text-[rgba(237,232,223,0.5)]'"
                                >{{ $name }}</button>
                            @endforeach
                        </div>
                        @error('type') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>

                    <div class="{{ $field }}">
                        <label for="sv-date" class="{{ $label }}">{{ __('Preferred Date & Time') }}</label>
                        <input id="sv-date" type="datetime-local" wire:model="meetingAt" min="{{ now()->addHour()->format('Y-m-d\TH:i') }}" dir="ltr" class="{{ $input }} rtl:text-right" />
                        @error('meetingAt') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>

                    <div class="{{ $field }}">
                        <label for="sv-notes" class="{{ $label }}">{{ __('Message') }} {!! $optional !!}</label>
                        <textarea id="sv-notes" wire:model="notes" rows="3" class="{{ $input }} resize-none leading-[1.6] rtl:font-arabic" placeholder="{{ __('Any specific questions or requirements…') }}"></textarea>
                        @error('notes') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="send" class="mt-1.5 w-full cursor-pointer rounded-full bg-brand p-[14px] text-[14px] font-[650] tracking-[0.04em] text-white transition-[background,translate] duration-250 hover:-translate-y-px hover:bg-[#a50d26] disabled:opacity-60 rtl:font-arabic">{{ __('Confirm Viewing Request') }}</button>
                </form>
            @endif
        </div>
    </div>
</div>
