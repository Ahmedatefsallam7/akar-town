{{--
    The account page's "My details" and "Password" forms (App\Livewire\Website\AccountSettings); the page's tabs
    (Alpine "tab") show one at a time.
--}}
@php
    $label = 'text-[11px] font-semibold tracking-[0.08em] text-ink-60 uppercase rtl:font-arabic rtl:tracking-normal';
    $input = 'w-full rounded-[10px] border-[1.5px] border-[rgba(13,13,13,0.16)] bg-white px-[14px] py-[11px] font-sans text-[14px] text-ink outline-none transition-[border-color,box-shadow] duration-200 placeholder:text-[rgba(13,13,13,0.42)] focus:border-brand focus:shadow-[0_0_0_3px_rgba(200,16,46,0.08)] dark:border-white/16 dark:bg-white/5 dark:placeholder:text-white/38';
    $error = 'block text-[12px] text-danger rtl:font-arabic';
    $field = 'flex flex-col gap-1.5';
    $button = 'inline-flex cursor-pointer items-center gap-2 self-start rounded-full bg-brand px-7 py-[13px] text-[13px] font-[650] tracking-[0.04em] text-white transition-[background,translate] duration-250 hover:-translate-y-px hover:bg-[#a50d26] disabled:opacity-60 rtl:font-arabic';
    $ok = 'rounded-[10px] border border-[rgba(25,160,70,0.25)] bg-[rgba(25,160,70,0.07)] px-4 py-3 text-[13px] text-[#15803d] rtl:font-arabic';
@endphp

<div>
    {{-- My details --}}
    <form x-show="tab === 'details'" wire:submit="saveDetails" class="flex flex-col gap-5" novalidate>
        @if ($saved === 'details')
            <p class="{{ $ok }}" role="status">{{ __('Your details are saved.') }}</p>
        @elseif ($saved === 'email')
            <p class="{{ $ok }}" role="status">{{ __('Your details are saved. We sent a link to your new e-mail: open it to verify the address.') }}</p>
        @endif
        @unless ($verified)
            <p class="rounded-[10px] border border-[rgba(217,119,6,0.25)] bg-[rgba(217,119,6,0.07)] px-4 py-3 text-[13px] text-[#b45309] rtl:font-arabic">{{ __('Your e-mail isn’t verified yet: open the link we sent you.') }}</p>
        @endunless

        <div class="{{ $field }}">
            <label for="acc-name" class="{{ $label }}">{{ __('Full Name') }}</label>
            <input id="acc-name" type="text" wire:model="name" autocomplete="name" class="{{ $input }} rtl:font-arabic" />
            @error('name') <span class="{{ $error }}">{{ $message }}</span> @enderror
        </div>
        <div class="grid grid-cols-[repeat(2,minmax(0,1fr))] gap-4 max-[600px]:grid-cols-1">
            <div class="{{ $field }}">
                <label for="acc-email" class="{{ $label }}">{{ __('Email Address') }}</label>
                <input id="acc-email" type="email" wire:model="email" autocomplete="email" dir="ltr" class="{{ $input }} rtl:text-right" />
                @error('email') <span class="{{ $error }}">{{ $message }}</span> @enderror
            </div>
            <div class="{{ $field }}">
                <label for="acc-phone" class="{{ $label }}">{{ __('Phone Number') }}</label>
                <input id="acc-phone" type="tel" wire:model="phone" autocomplete="tel" dir="ltr" class="{{ $input }} rtl:text-right" placeholder="+20 1XX XXX XXXX" />
                @error('phone') <span class="{{ $error }}">{{ $message }}</span> @enderror
            </div>
        </div>
        <button type="submit" wire:loading.attr="disabled" wire:target="saveDetails" class="{{ $button }}">{{ __('Save changes') }}</button>
    </form>

    {{-- Password --}}
    <form x-show="tab === 'password'" x-cloak wire:submit="savePassword" class="flex max-w-[480px] flex-col gap-5" novalidate>
        @if ($saved === 'password')
            <p class="{{ $ok }}" role="status">{{ __('Your password is changed.') }}</p>
        @endif
        <div class="{{ $field }}">
            <label for="acc-current" class="{{ $label }}">{{ __('Current password') }}</label>
            <input id="acc-current" type="password" wire:model="currentPassword" autocomplete="current-password" class="{{ $input }}" />
            @error('currentPassword') <span class="{{ $error }}">{{ $message }}</span> @enderror
        </div>
        <div class="{{ $field }}">
            <label for="acc-new" class="{{ $label }}">{{ __('New password') }}</label>
            <input id="acc-new" type="password" wire:model="password" autocomplete="new-password" class="{{ $input }}" />
            @error('password') <span class="{{ $error }}">{{ $message }}</span> @enderror
        </div>
        <div class="{{ $field }}">
            <label for="acc-confirm" class="{{ $label }}">{{ __('Confirm password') }}</label>
            <input id="acc-confirm" type="password" wire:model="passwordConfirmation" autocomplete="new-password" class="{{ $input }}" />
            @error('passwordConfirmation') <span class="{{ $error }}">{{ $message }}</span> @enderror
        </div>
        <button type="submit" wire:loading.attr="disabled" wire:target="savePassword" class="{{ $button }}">{{ __('Change password') }}</button>
    </form>
</div>
