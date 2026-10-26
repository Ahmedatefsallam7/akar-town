{{-- Privacy Policy (Settings → Privacy Policy: introduction and sections). --}}
@php($settings = app(\Modules\GeneralSettings\Settings\PrivacyPolicySettings::class))

<x-website.layout :title="__('Privacy Policy')" :description="\Illuminate\Support\Str::limit($settings->{'description_'.app()->getLocale()}, 160)">
    <x-website.legal-page
        :title="__('Privacy Policy')"
        :description="$settings->{'description_'.app()->getLocale()}"
        :entries="$settings->entries"
        :question="__('Questions about your privacy?')"
    />
</x-website.layout>
