{{-- Projects listing (the design's projects.html): the Livewire search, then the CTA banner. --}}
<x-website.layout :title="__('Projects')">
    <livewire:website.projects />

    <x-website.cta-banner
        :eyebrow="__('Personalised Service')"
        :title="__('Can’t Find Your<br><em>Dream</em> Project?')"
        :description="__('Our specialists curate exclusive off-market opportunities tailored to your investment goals and lifestyle preferences.')"
    />
</x-website.layout>
