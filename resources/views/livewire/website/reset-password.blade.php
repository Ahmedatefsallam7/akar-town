{{--
    The new password form (App\Livewire\Website\ResetPassword), in the sign-in window's style: the e-mail from the
    link, the new password twice.
--}}
@php
    $label = 'mb-2 block text-[12.5px] font-semibold tracking-[0.01em] text-[#0d0d0d] dark:text-[#d8d4ce] rtl:font-arabic';
    $input = 'h-12 w-full rounded-[10px] border-[1.5px] border-[#e0ddd8] bg-white px-[14px] font-[inherit] text-[14px] text-[#0d0d0d] outline-none transition-[border-color] duration-200 placeholder:text-black/28 focus:border-brand dark:border-white/10 dark:bg-[#1f1f1f] dark:text-[#f0ede8] dark:placeholder:text-white/20';
    $error = 'mt-1.5 block text-[12px] text-danger rtl:font-arabic';
@endphp

<section class="flex min-h-[70vh] items-center justify-center bg-page px-4 pt-[calc(var(--spacing-nav)+48px)] pb-16">
    <form wire:submit="save" class="w-full max-w-[440px] rounded-[18px] border border-ink-10 bg-white px-10 pt-11 pb-9 dark:border-white/10 dark:bg-[#161616] max-[420px]:px-[18px]" novalidate>
        <h1 class="mb-2 text-[20px] font-bold tracking-[-0.02em] text-[#0d0d0d] dark:text-[#f0ede8] rtl:font-arabic">{{ __('Reset Password') }}</h1>
        <p class="mb-7 text-[13px] leading-[1.5] text-black/55 dark:text-white/50 rtl:font-arabic">{{ __('Choose a new password for your account.') }}</p>

        <label for="reset-email" class="{{ $label }}">{{ __('Email') }}</label>
        <input id="reset-email" type="email" value="{{ $email }}" readonly dir="ltr" class="{{ $input }} mb-4 bg-black/3! text-black/60! dark:bg-white/5! dark:text-white/60! rtl:text-right" />

        <label for="reset-password" class="{{ $label }}">{{ __('New password') }}</label>
        <input id="reset-password" type="password" wire:model="password" autocomplete="new-password" class="{{ $input }}" />
        @error('password') <span class="{{ $error }}">{{ $message }}</span> @enderror

        <label for="reset-password-confirmation" class="{{ $label }} mt-4">{{ __('Confirm password') }}</label>
        <input id="reset-password-confirmation" type="password" wire:model="passwordConfirmation" autocomplete="new-password" class="{{ $input }}" />
        @error('passwordConfirmation') <span class="{{ $error }}">{{ $message }}</span> @enderror

        <button type="submit" wire:loading.attr="disabled" class="mt-6 h-12 w-full cursor-pointer rounded-[10px] border-none bg-brand text-[14px] font-bold tracking-[0.02em] text-white transition-[background,translate] duration-200 hover:-translate-y-px hover:bg-[#a50d26] disabled:opacity-70 rtl:font-arabic">{{ __('Save and sign in') }}</button>
    </form>
</section>
