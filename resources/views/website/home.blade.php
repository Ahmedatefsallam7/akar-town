{{-- Home page (the design's index.html). The blocks under the search follow the admin's home sliders. --}}
<x-website.layout transparent-nav reveal-footer>
    <x-slot:before>
        @include('website.home.splash')
    </x-slot:before>

    @if ($slides)
        @include('website.home.hero')
    @else
        <section class="h-[88vh] min-h-[580px] bg-night"></section>
    @endif

    @include('website.home.quick-search')

    @php($whyShown = false)
    @foreach ($sections as $section)
        @switch($section['type'])
            @case('properties')
                @if ($featured)
                    @include('website.home.featured', ['title' => $section['title']])
                @endif
                @break
            @case('locations')
                @if ($locations)
                    @include('website.home.locations', ['title' => $section['title']])
                @endif
                @break
            @case('projects')
                @if ($projects)
                    @include('website.home.projects', ['title' => $section['title']])
                @endif
                @break
        @endswitch

        {{-- As in the design, "Why Choose Us" follows the first block. --}}
        @if (! $whyShown)
            @include('website.home.why')
            @php($whyShown = true)
        @endif
    @endforeach

    @if (! $whyShown)
        @include('website.home.why')
    @endif
</x-website.layout>
