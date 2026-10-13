{{--
    "Set location on map": click (or drag the pin) to fill Latitude / Longitude; typing them moves the pin.
    Alpine + Leaflet (resources/js/filament/map-picker.js), OpenStreetMap tiles.
--}}
@php
    $container = $getContainer()->getStatePath();
    $disabled = $isDisabled();
@endphp

@once
    @vite('resources/js/filament/map-picker.js')
@endonce

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        wire:ignore
        x-data="{
            lat: $wire.$entangle('{{ $container }}.latitude'),
            lng: $wire.$entangle('{{ $container }}.longitude'),
            map: null,
            marker: null,
            init() {
                window.L ? this.start() : window.addEventListener('map-picker:ready', () => this.start(), { once: true })
            },
            start() {
                const has = this.lat !== null && this.lat !== '' && this.lng !== null && this.lng !== ''
                // Default view: Cairo.
                this.map = L.map(this.$refs.map, { scrollWheelZoom: false })
                    .setView(has ? [this.lat, this.lng] : [30.0444, 31.2357], has ? 15 : 10)
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap',
                }).addTo(this.map)
                if (has) this.place(this.lat, this.lng)
                @if (! $disabled)
                    this.map.on('click', (e) => this.set(e.latlng))
                @endif
                this.$watch('lat', () => this.sync())
                this.$watch('lng', () => this.sync())
                // The map may start in a hidden tab: size it when it shows.
                new ResizeObserver(() => this.map.invalidateSize()).observe(this.$refs.map)
            },
            place(lat, lng) {
                if (this.marker) return this.marker.setLatLng([lat, lng])
                this.marker = L.marker([lat, lng], { draggable: {{ $disabled ? 'false' : 'true' }} }).addTo(this.map)
                this.marker.on('dragend', () => this.set(this.marker.getLatLng()))
            },
            set(point) {
                this.lat = Number(point.lat.toFixed(7))
                this.lng = Number(point.lng.toFixed(7))
                this.place(this.lat, this.lng)
            },
            sync() {
                const lat = parseFloat(this.lat), lng = parseFloat(this.lng)
                if (Number.isNaN(lat) || Number.isNaN(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) return
                this.place(lat, lng)
                this.map.panTo([lat, lng])
            },
        }"
    >
        <div x-ref="map" class="relative z-0 h-80 w-full overflow-hidden rounded-lg border border-gray-200"></div>
    </div>
</x-dynamic-component>
