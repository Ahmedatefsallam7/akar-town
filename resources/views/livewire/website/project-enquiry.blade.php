{{--
    The project page's "Send an Enquiry" form (App\Livewire\Website\ProjectEnquiry), as the design's #projContactForm
    (inside the dark contact section). A unit type's "Enquire About This Unit" picks that type here
    (window event "project-unit-type").
--}}
@php
    $label = 'text-[9.5px] font-bold tracking-[0.18em] text-white/40 uppercase rtl:font-arabic rtl:tracking-[0.04em]';
    $field = 'w-full appearance-none rounded-[10px] border-[1.5px] border-white/10 bg-white/6 px-4 py-3 font-sans text-[13.5px] font-light text-white outline-none transition-[border-color,background,box-shadow] duration-220 placeholder:text-white/28 focus:border-[rgba(200,16,46,0.55)] focus:bg-white/9 focus:shadow-[0_0_0_3px_rgba(200,16,46,0.1)] rtl:font-arabic';
    $select = $field.' cursor-pointer pr-10 rtl:pr-4 rtl:pl-10 [&_option]:bg-[rgb(26,24,20)] [&_option]:text-white';
    $chevron = '<svg width="12" height="8" viewBox="0 0 12 8" fill="none" aria-hidden="true" class="pointer-events-none absolute top-1/2 right-[14px] -translate-y-1/2 rtl:right-auto rtl:left-[14px]"><path d="M1 1l5 5 5-5" stroke="rgba(255,255,255,0.4)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>';
    $error = 'text-[11px] text-[#ff8a8a]';
    $arrow = '<svg width="14" height="12" viewBox="0 0 16 14" fill="none" aria-hidden="true" class="rtl:-scale-x-100"><path d="M7.99997 0.414307C7.99997 1.11051 8.68991 2.15231 9.38737 3.02724C10.2855 4.15371 11.3574 5.13777 12.5873 5.88911C13.5087 6.45171 14.6276 6.99177 15.5264 6.99177M7.99997 13.5855C7.99997 12.8893 8.68991 11.8475 9.38737 10.9726C10.2855 9.84617 11.3574 8.86204 12.5873 8.11071C13.5087 7.54811 14.6276 7.00804 15.5264 7.00804M15.5264 6.99991H0.473572" stroke="currentColor" /></svg>';
@endphp

<div x-on:project-unit-type.window="$wire.type = String($event.detail)">
    @if ($sent)
        <p class="rounded-[10px] border-[1.5px] border-white/10 bg-white/6 px-4 py-3 text-[13.5px] leading-[1.6] text-white" role="status">
            {{ __("Enquiry sent — we'll be in touch shortly!") }}
        </p>
    @else
        <form wire:submit="send" class="flex flex-col gap-4" novalidate>
            <div class="grid grid-cols-2 gap-[14px] max-[600px]:grid-cols-1">
                <div class="flex flex-col gap-[7px]">
                    <label for="pe-name" class="{{ $label }}">{{ __('Full Name') }}</label>
                    <input id="pe-name" type="text" wire:model="fullName" autocomplete="name" class="{{ $field }}" placeholder="{{ __('Your full name') }}" />
                    @error('fullName') <span class="{{ $error }}">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col gap-[7px]">
                    <label for="pe-phone" class="{{ $label }}">{{ __('Phone Number') }}</label>
                    <input id="pe-phone" type="tel" wire:model="phone" autocomplete="tel" dir="ltr" class="{{ $field }} rtl:text-right" placeholder="+20 xxx xxx xxxx" />
                    @error('phone') <span class="{{ $error }}">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex flex-col gap-[7px]">
                <label for="pe-email" class="{{ $label }}">{{ __('Email Address') }}</label>
                <input id="pe-email" type="email" wire:model="email" autocomplete="email" class="{{ $field }}" placeholder="you@example.com" />
                @error('email') <span class="{{ $error }}">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-2 gap-[14px] max-[600px]:grid-cols-1">
                @if ($types)
                    <div class="flex flex-col gap-[7px]">
                        <label for="pe-type" class="{{ $label }}">{{ __('Unit Type') }}</label>
                        <div class="relative">
                            <select id="pe-type" wire:model="type" class="{{ $select }}">
                                <option value="">{{ __('Select a type') }}</option>
                                @foreach ($types as [$id, $name, $area])
                                    <option value="{{ $id }}">{{ $area ? $name.' ('.$area.' '.__('m²').')' : $name }}</option>
                                @endforeach
                            </select>
                            {!! $chevron !!}
                        </div>
                        @error('type') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>
                @endif
                <div @class(['flex flex-col gap-[7px]', 'col-span-2 max-[600px]:col-span-1' => ! $types])>
                    <label for="pe-budget" class="{{ $label }}">{{ __('Budget Range') }}</label>
                    <div class="relative">
                        <select id="pe-budget" wire:model="budget" class="{{ $select }}">
                            <option value="">{{ __('Select a range') }}</option>
                            @foreach (\App\Livewire\Website\ProjectEnquiry::BUDGETS as $key => $text)
                                <option value="{{ $key }}">{{ __($text) }}</option>
                            @endforeach
                        </select>
                        {!! $chevron !!}
                    </div>
                    @error('budget') <span class="{{ $error }}">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex flex-col gap-[7px]">
                <label for="pe-message" class="{{ $label }}">{{ __('Message') }} <span class="font-normal opacity-50">{{ __('(optional)') }}</span></label>
                <textarea id="pe-message" wire:model="message" rows="4" class="{{ $field }} min-h-[108px] resize-y leading-[1.6]" placeholder="{{ __("Tell us more about what you're looking for…") }}"></textarea>
                @error('message') <span class="{{ $error }}">{{ $message }}</span> @enderror
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="mt-1 flex w-full cursor-pointer items-center justify-center gap-[9px] rounded-full border border-white/18 bg-[linear-gradient(135deg,rgba(215,20,52,0.96)_0%,rgb(185,10,36)_100%)] px-6 py-[15px] font-sans text-[12px] leading-[normal] font-bold tracking-[0.12em] text-white uppercase transition-[translate,opacity] duration-250 ease-out-ex hover:-translate-y-0.5 disabled:translate-y-0 disabled:cursor-default disabled:opacity-65 rtl:font-arabic rtl:tracking-normal"
            >
                <span>{{ __('Send Enquiry') }}</span>
                <span wire:loading wire:target="send" class="size-[13px] animate-spin rounded-full border-2 border-white border-x-white/35 border-b-white/35" aria-hidden="true"></span>
                <span wire:loading.remove wire:target="send" class="flex">{!! $arrow !!}</span>
            </button>

            <p class="mt-3 text-center text-[10.5px] leading-[1.6] font-light text-white/28">{{ __('Your information is kept private and will only be used to contact you about this project.') }}</p>
        </form>
    @endif
</div>
