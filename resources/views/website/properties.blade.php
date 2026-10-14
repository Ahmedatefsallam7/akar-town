{{-- Properties listing (the design's listings.html): page header, then the Livewire search. --}}
<x-website.layout :title="__('Properties')">
    <x-website.page-hero :crumb="__('Properties')" :title="__('Discover <em>Exceptional</em> Properties')" :count="$available" :count-label="__('Available Properties')" />

    <livewire:website.properties />
</x-website.layout>
