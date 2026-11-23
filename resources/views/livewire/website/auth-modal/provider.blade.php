{{--
    The sign-in window's Google / Facebook button (AKAR-55): a link to the provider when its keys are in .env
    (SocialSignInController::enabled), otherwise "coming soon". Takes $provider ('google' / 'facebook') and $icon.
--}}
@php
    $providerName = \App\Http\Controllers\Website\SocialSignInController::PROVIDERS[$provider];
    $providerLabel = __('Continue with '.$providerName);
@endphp
@if (\App\Http\Controllers\Website\SocialSignInController::enabled($provider))
    <a href="{{ route('auth.social', $provider) }}" class="{{ $social }}" title="{{ $providerLabel }}" aria-label="{{ $providerLabel }}">{!! $icon !!}</a>
@else
    <button type="button" @click="comingSoon(@js($providerName))" class="{{ $social }}" title="{{ $providerLabel }}" aria-label="{{ $providerLabel }}">{!! $icon !!}</button>
@endif
