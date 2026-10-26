{{-- Terms of Use (Settings → Terms of Use: introduction and sections). --}}
@php($settings = app(\Modules\GeneralSettings\Settings\TermsOfUseSettings::class))

<x-website.layout :title="__('Terms of Use')" :description="\Illuminate\Support\Str::limit($settings->{'description_'.app()->getLocale()}, 160)">
    <x-website.legal-page
        :title="__('Terms of Use')"
        :description="$settings->{'description_'.app()->getLocale()}"
        :entries="$settings->entries"
        :question="__('Questions about these terms?')"
    />
</x-website.layout>
