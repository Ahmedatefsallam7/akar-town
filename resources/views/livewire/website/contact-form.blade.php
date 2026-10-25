{{--
    The contact page's "Send Us a Message" form (App\Livewire\Website\ContactForm), as the design's #contactForm with
    its custom "I'm Interested In" dropdown (.cf-dropdown).
--}}
@php
    use App\Livewire\Website\ContactForm;

    $label = 'mb-2 text-[9px] font-bold tracking-[0.22em] text-ink-30 uppercase transition-colors duration-200 group-focus-within/field:text-brand rtl:font-arabic rtl:tracking-[0.04em]';
    $box = 'w-full rounded-[14px] border-[1.5px] border-[rgba(13,13,13,0.1)] bg-white px-4 py-[13px] font-sans text-[14px] leading-[normal] text-ink outline-none [transition:border-color_0.25s_var(--ease-out-ex),box-shadow_0.25s_var(--ease-out-ex),background_0.25s] hover:border-[rgba(13,13,13,0.22)] rtl:font-arabic dark:border-white/10 dark:bg-white/5 dark:hover:border-white/22';
    $focus = 'focus:border-brand focus:shadow-[0_0_0_4px_rgba(200,16,46,0.09)] dark:focus:bg-white/7 dark:focus:shadow-[0_0_0_4px_rgba(200,16,46,0.12)]';
    $field = $box.' '.$focus.' appearance-none placeholder:font-light placeholder:text-ink-30';
    $error = 'mt-1.5 block text-[12px] text-danger rtl:font-arabic';
    $interests = collect(ContactForm::INTERESTS)->map(fn (string $interest) => __($interest))->all();
@endphp

<div>
    @if ($sent)
        <p class="rounded-[14px] border-[1.5px] border-[rgba(13,13,13,0.1)] bg-white px-4 py-[13px] text-[14px] leading-[1.6] text-ink rtl:font-arabic dark:border-white/10 dark:bg-white/5" role="status">
            {{ __("Message sent — we'll be in touch within one business day.") }}
        </p>
    @else
        <form wire:submit="send" class="flex flex-col" novalidate>
            <div class="grid grid-cols-2 gap-5 max-md:grid-cols-1 max-md:gap-0">
                <div class="group/field mb-6 flex flex-col">
                    <label for="cf-name" class="{{ $label }}">{{ __('Full Name') }}</label>
                    <input id="cf-name" type="text" wire:model="fullName" autocomplete="name" class="{{ $field }}" placeholder="{{ __('John Doe') }}" />
                    @error('fullName') <span class="{{ $error }}">{{ $message }}</span> @enderror
                </div>
                <div class="group/field mb-6 flex flex-col">
                    <label for="cf-phone" class="{{ $label }}">{{ __('Phone Number') }}</label>
                    <input id="cf-phone" type="tel" wire:model="phone" autocomplete="tel" dir="ltr" class="{{ $field }} rtl:text-right" placeholder="+20 100 000 0000" />
                    @error('phone') <span class="{{ $error }}">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="group/field mb-6 flex flex-col">
                <label for="cf-email" class="{{ $label }}">{{ __('Email Address') }}</label>
                <input id="cf-email" type="email" wire:model="email" autocomplete="email" class="{{ $field }}" placeholder="you@example.com" />
                @error('email') <span class="{{ $error }}">{{ $message }}</span> @enderror
            </div>

            <div class="group/field mb-6 flex flex-col">
                <span id="cf-interest-label" class="{{ $label }}">{{ __("I'm Interested In") }}</span>
                <div
                    x-data="{ open: false, options: @js($interests) }"
                    @click.outside="open = false"
                    @keydown.escape="open = false"
                    class="relative w-full select-none"
                >
                    <button
                        type="button"
                        @click="open = ! open"
                        :aria-expanded="open"
                        aria-haspopup="listbox"
                        aria-labelledby="cf-interest-label"
                        class="{{ $box }} flex cursor-pointer items-center justify-between gap-[10px] text-start"
                        :class="{
                            'text-ink': $wire.interest,
                            'text-ink-30': ! $wire.interest,
                            'border-brand! shadow-[0_0_0_4px_rgba(200,16,46,0.09)] dark:bg-white/7 dark:shadow-[0_0_0_4px_rgba(200,16,46,0.12)]': open,
                        }"
                    >
                        <span x-text="options[$wire.interest] ?? @js(__('Select an option'))">{{ __('Select an option') }}</span>
                        <svg width="12" height="8" viewBox="0 0 12 8" fill="none" aria-hidden="true" class="shrink-0 transition-transform duration-300 ease-out-ex" :class="open ? 'rotate-180 text-brand' : 'text-ink-30'"><path d="M1 1l5 5 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    </button>
                    <div
                        role="listbox"
                        aria-labelledby="cf-interest-label"
                        class="absolute inset-x-0 top-[calc(100%+6px)] z-50 origin-top overflow-hidden rounded-xl border border-ink-10 bg-white shadow-[0_4px_16px_rgba(13,13,13,0.06),0_16px_48px_rgba(13,13,13,0.1)] [transition:opacity_0.22s_var(--ease-out-ex),transform_0.22s_var(--ease-out-ex)] dark:border-white/10 dark:bg-[#1a1916] dark:shadow-[0_4px_16px_rgba(0,0,0,0.3),0_16px_48px_rgba(0,0,0,0.4)]"
                        :class="open ? 'pointer-events-auto opacity-100 [transform:none]' : 'pointer-events-none opacity-0 [transform:translateY(-6px)_scale(0.98)]'"
                    >
                        <template x-for="(name, value) in options" :key="value">
                            <div
                                role="option"
                                :aria-selected="$wire.interest === value"
                                @click="$wire.interest = value; open = false"
                                class="flex cursor-pointer items-center justify-between border-b border-ink-10 px-4 py-[13px] text-[14px] transition-[background,color] duration-150 last:border-b-0 hover:bg-[rgba(200,16,46,0.04)] hover:text-ink rtl:font-arabic dark:border-white/6 dark:hover:bg-[rgba(200,16,46,0.08)]"
                                :class="$wire.interest === value ? 'bg-[rgba(200,16,46,0.04)] font-semibold text-brand dark:bg-[rgba(200,16,46,0.08)]' : 'text-ink-60'"
                            >
                                <span x-text="name"></span>
                                <svg width="14" height="11" viewBox="0 0 14 11" fill="none" aria-hidden="true" class="shrink-0 text-brand transition-opacity duration-150" :class="$wire.interest === value ? 'opacity-100' : 'opacity-0'"><path d="M1 5.5l4 4L13 1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                            </div>
                        </template>
                    </div>
                </div>
                @error('interest') <span class="{{ $error }}">{{ $message }}</span> @enderror
            </div>

            <div class="group/field mb-6 flex flex-col">
                <label for="cf-message" class="{{ $label }}">{{ __('Your Message') }}</label>
                <textarea id="cf-message" wire:model="message" class="{{ $field }} h-[120px] resize-none leading-[1.65]!" placeholder="{{ __('Tell us about your requirements, budget, preferred location…') }}"></textarea>
                @error('message') <span class="{{ $error }}">{{ $message }}</span> @enderror
            </div>

            <div class="mt-2 self-start">
                <button type="submit" wire:loading.attr="disabled" class="group inline-flex cursor-pointer items-center gap-2 rounded-full border border-white/20 bg-[linear-gradient(135deg,rgba(215,20,52,0.96)_0%,rgb(185,10,36)_100%)] px-[30px] py-[14px] font-sans text-[13px] leading-none font-[650] tracking-[0.055em] whitespace-nowrap text-white backdrop-blur-[20px] transition-all duration-300 ease-out-ex hover:-translate-y-0.5 hover:bg-[linear-gradient(135deg,rgba(225,25,58,0.98)_0%,rgb(200,16,46)_100%)] disabled:opacity-60 rtl:font-arabic">
                    {{ __('Send Message') }}
                    <svg width="14" height="10" viewBox="0 0 16 12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="rtl:-scale-x-100"><path d="M1 6h14M9 1l6 5-6 5" /></svg>
                </button>
            </div>
            <p class="mt-[14px] text-[11px] leading-[1.6] text-ink-30 rtl:font-arabic">{{ __('By submitting this form, you agree to be contacted by Akar Town regarding your inquiry. We respect your privacy.') }}</p>
        </form>
    @endif
</div>
