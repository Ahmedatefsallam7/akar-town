{{-- Home page (the design's index.html). The blocks under the search follow the admin's home sliders. --}}
<x-website.layout transparent-nav reveal-footer intro>
    <x-slot:before>
        @include('website.home.splash')
    </x-slot:before>

    @if ($slides)
        @include('website.home.hero')
    @else
        <section class="h-[88vh] min-h-[580px] bg-night"></section>
    @endif

    @include('website.home.quick-search')

    {{-- The sections below are set up by Alpine as they come near the screen (data-alpine-later, website/boot.js). --}}

    @php($whyShown = false)
    @foreach ($sections as $section)
        @switch($section['type'])
            @case('properties')
                @if ($featured)
                    <div x-data x-ignore data-alpine-later="near" class="contents">@include('website.home.featured', ['title' => $section['title']])</div>
                @endif
                @break
            @case('locations')
                @if ($locations)
                    <div x-data x-ignore data-alpine-later="near" class="contents">@include('website.home.locations', ['title' => $section['title']])</div>
                @endif
                @break
            @case('projects')
                @if ($projects)
                    <div x-data x-ignore data-alpine-later="near" class="contents">@include('website.home.projects', ['title' => $section['title']])</div>
                @endif
                @break
            @case('cta_ribbon')
                {{-- Settings → CTA Ribbon, shown as the design's CTA banner. --}}
                @php($ribbon = app(\Modules\GeneralSettings\Settings\CtaRibbonSettings::class))
                <x-website.cta-banner
                    :eyebrow="__('Begin Your Journey')"
                    :title="\App\Support\Website\HomeSections::accent($ribbon->{'header_'.app()->getLocale()})"
                    :description="$ribbon->{'body_'.app()->getLocale()}"
                    variant="contact"
                />
                @break
        @endswitch

        {{-- As in the design, "Why Choose Us" follows the first block. --}}
        @if (! $whyShown)
            <div x-data x-ignore data-alpine-later="near" class="contents">@include('website.home.why')</div>
            @php($whyShown = true)
        @endif
    @endforeach

    @if (! $whyShown)
        <div x-data x-ignore data-alpine-later="near" class="contents">@include('website.home.why')</div>
    @endif
</x-website.layout>
