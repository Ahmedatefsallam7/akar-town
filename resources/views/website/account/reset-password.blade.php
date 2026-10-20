{{-- The "reset your password" e-mail's page: the new password form (App\Livewire\Website\ResetPassword). --}}
<x-website.layout :title="__('Reset Password')">
    <livewire:website.reset-password :token="request()->route('token')" />
</x-website.layout>
