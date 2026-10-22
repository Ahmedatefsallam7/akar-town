{{--
    One location's page: its photo, name and description in the listing header (not in the design: the design's card
    opens the properties listing filtered by the location), then the properties search kept to it.
--}}
<x-website.layout :title="$metaTitle" :description="$metaDescription">
    <x-website.page-hero
        :crumb="$name"
        :parent="['label' => __('Locations'), 'url' => route('locations')]"
        :title="e($name)"
        :description="$description ? \Illuminate\Support\Str::limit(strip_tags($description), 220) : null"
        :image="$image"
        :count="$count"
        :count-label="__('Available Properties')"
    />

    <livewire:website.properties :place="$slug" />
</x-website.layout>
