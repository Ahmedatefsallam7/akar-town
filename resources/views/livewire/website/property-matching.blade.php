{{--
    The footer's "Property Matching" form (App\Livewire\Website\PropertyMatching): the design's unit type chips, budget
    and email, plus name and phone. Looks as in the design (inside the dark footer). On short screens (≤720px tall) the
    "Your details" / "Your email" labels hide (the fields' placeholders say it), so the footer's wordmark fits whole.
    @var array<string, string> $types  unit type slug => name
--}}
@php
    $label = 'mb-[0.9rem] block text-[0.68rem] font-semibold tracking-[0.16em] text-cream/55 uppercase rtl:tracking-[0.04em]';
    $fieldLabel = 'mb-[0.45rem] block text-[0.6rem] font-bold tracking-[0.1em] text-cream/60 uppercase';
    $input = 'w-full min-w-0 flex-1 rounded-[10px] border-[1.5px] border-cream/14 bg-cream/5 px-3 py-[10px] text-[0.8rem] text-cream placeholder:text-cream/42 dark:border-line-md dark:bg-surface-2 dark:text-ink dark:placeholder:text-ink-30';
    $error = 'mt-1 block text-[0.7rem] text-[#ff8a8a]';
@endphp

<div>
    <span class="{{ $label }}">{{ __('Property Matching') }}</span>
    <h3 class="mb-2 text-[clamp(1.1rem,1.8vw,1.5rem)] leading-[1.2] font-bold text-cream [&_em]:text-[#c8102e] [&_em]:not-italic">{!! __('Find Your <em>Perfect</em> Unit') !!}</h3>

    @if ($sent)
        <p class="mt-4 rounded-[10px] border-[1.5px] border-cream/14 bg-cream/5 px-4 py-3 text-[0.85rem] leading-[1.55] text-cream" role="status">
            {{ __("Thank you! We've got your request and our team will contact you soon.") }}
        </p>
    @else
        <p class="mb-4 text-[0.82rem] leading-[1.55] text-cream/75">{{ __("Tell us what you need — we'll alert you the moment it's listed.") }}</p>

        @if ($types)
            <div class="mb-4 flex flex-wrap gap-[0.4rem]" role="group" aria-label="{{ __('Property type') }}">
                @foreach ($types as $slug => $typeLabel)
                    <button
                        type="button"
                        @click="$wire.type = '{{ $slug }}'"
                        @class([
                            'cursor-pointer rounded-[99px] border px-[0.9rem] py-[0.38rem] text-[0.78rem] leading-none font-medium transition-[background,border-color,color] duration-200 hover:border-[#c8102e] hover:bg-[#c8102e] hover:text-white',
                            'border-[#c8102e] bg-[#c8102e] text-white' => $type === $slug,
                            'border-cream/38 bg-transparent text-cream/88' => $type !== $slug,
                        ])
                        :class="{ 'border-[#c8102e] bg-[#c8102e] text-white': $wire.type === '{{ $slug }}', 'border-cream/38 bg-transparent text-cream/88': $wire.type !== '{{ $slug }}' }"
                        :aria-pressed="$wire.type === '{{ $slug }}'"
                    >{{ $typeLabel }}</button>
                @endforeach
            </div>
        @endif

        <form wire:submit="send" novalidate>
            <span class="{{ $fieldLabel }}">{{ __('Budget Range (EGP)') }}</span>
            <div class="mb-[0.85rem]">
                <div class="flex items-center gap-[0.4rem] max-xs:flex-wrap">
                    <input type="number" min="0" wire:model="budgetMin" class="{{ $input }}" placeholder="{{ __('Minimum') }}" aria-label="{{ __('Minimum budget') }}" />
                    <span class="shrink-0 text-[1rem] text-cream/55" aria-hidden="true">—</span>
                    <input type="number" min="0" wire:model="budgetMax" class="{{ $input }}" placeholder="{{ __('Maximum') }}" aria-label="{{ __('Maximum budget') }}" />
                </div>
                @error('budgetMin') <span class="{{ $error }}">{{ $message }}</span> @enderror
                @error('budgetMax') <span class="{{ $error }}">{{ $message }}</span> @enderror
            </div>

            <span class="{{ $fieldLabel }} [@media(max-height:720px)]:hidden">{{ __('Your Details') }}</span>
            <div class="mb-[0.85rem] grid grid-cols-[repeat(2,minmax(0,1fr))] gap-[0.4rem] max-xs:grid-cols-1">
                <div class="flex flex-col">
                    <input type="text" wire:model="fullName" autocomplete="name" class="{{ $input }}" placeholder="{{ __('Full name') }}" aria-label="{{ __('Full name') }}" />
                    @error('fullName') <span class="{{ $error }}">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col">
                    <input type="tel" wire:model="phone" autocomplete="tel" dir="ltr" class="{{ $input }} rtl:text-right" placeholder="{{ __('Phone') }}" aria-label="{{ __('Phone') }}" />
                    @error('phone') <span class="{{ $error }}">{{ $message }}</span> @enderror
                </div>
            </div>

            <span class="{{ $fieldLabel }} [@media(max-height:720px)]:hidden">{{ __('Your Email') }}</span>
            <div class="flex items-stretch gap-[0.45rem]">
                <input type="email" wire:model="email" autocomplete="email" class="{{ $input }}" placeholder="you@example.com" aria-label="{{ __('Your email') }}" />
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="flex size-[38px] shrink-0 cursor-pointer items-center justify-center rounded-full bg-[#c8102e] text-white transition-[background,opacity] duration-250 hover:bg-[#a60d24] disabled:opacity-60"
                    aria-label="{{ __('Submit') }}"
                >
                    <span wire:loading wire:target="send" class="size-[14px] animate-spin rounded-full border-2 border-white border-x-white/35 border-b-white/35" aria-hidden="true"></span>
                    <svg wire:loading.remove wire:target="send" width="16" height="14" viewBox="0 0 16 14" fill="none" aria-hidden="true" class="rtl:-scale-x-100">
                        <path d="M7.99997 0.414307C7.99997 1.11051 8.68991 2.15231 9.38737 3.02724C10.2855 4.15371 11.3574 5.13777 12.5873 5.88911C13.5087 6.45171 14.6276 6.99177 15.5264 6.99177M7.99997 13.5855C7.99997 12.8893 8.68991 11.8475 9.38737 10.9726C10.2855 9.84617 11.3574 8.86204 12.5873 8.11071C13.5087 7.54811 14.6276 7.00804 15.5264 7.00804M15.5264 6.99991H0.473572" stroke="currentColor" />
                    </svg>
                </button>
            </div>
            @error('email') <span class="{{ $error }}">{{ $message }}</span> @enderror
        </form>
    @endif
</div>
