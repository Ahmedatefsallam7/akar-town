{{-- Terms & Conditions (Settings → Terms & Conditions: introduction and sections). --}}
@php($settings = app(\Modules\GeneralSettings\Settings\TermsOfUseSettings::class))

<x-website.layout :title="__('Terms & Conditions')" :description="\Illuminate\Support\Str::limit($settings->{'description_'.app()->getLocale()}, 160)">
    <x-website.legal-page
        :title="__('Terms & Conditions')"
        :description="$settings->{'description_'.app()->getLocale()}"
        :entries="$settings->entries"
        :question="__('Questions about these terms?')"
    />
</x-website.layout>
