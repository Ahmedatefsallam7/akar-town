// Property details page: photo slider, sticky bar, "Read More", location map, 360° tour, similar properties and PDF language choice.
import Swiper from 'swiper'
import { Navigation } from 'swiper/modules'
// Swiper's own styles, with the page's code (pages load only their own code: the home slider's copy isn't here).
import 'swiper/css'

export default ({ count, latitude, longitude, place, tours = [], loading = 'Loading...' }) => ({
    current: 0,
    touring: false,
    tourIndex: 0,
    viewer: null,
    sticky: false,
    progress: 0,
    more: false,
    pdfOpen: false,

    init() {
        const onScroll = () => {
            const hero = this.$refs.hero.offsetHeight + this.$refs.crumb.offsetHeight
            const max = document.documentElement.scrollHeight - window.innerHeight
            this.sticky = window.scrollY > hero - 80
            this.progress = max > 0 ? Math.min(100, (window.scrollY / max) * 100) : 0
        }
        window.addEventListener('scroll', onScroll, { passive: true })
        onScroll()

        let touchX = null
        this.$refs.gallery?.addEventListener('touchstart', (e) => (touchX = e.touches[0].clientX), { passive: true })
        this.$refs.gallery?.addEventListener(
            'touchend',
            (e) => {
                if (touchX === null) return
                const dx = e.changedTouches[0].clientX - touchX
                if (Math.abs(dx) > 40) this.go(dx < 0 ? this.current + 1 : this.current - 1)
                touchX = null
            },
            { passive: true },
        )

        document.addEventListener('keydown', (e) => {
            if (this.$store.projectLightbox.open) return
            if (e.key === 'ArrowLeft') this.go(this.current - 1)
            if (e.key === 'ArrowRight') this.go(this.current + 1)
        })

        // The second photo once the page has loaded, so a swipe shows it at once.
        if (document.readyState === 'complete') this.photo(1)
        else window.addEventListener('load', () => this.photo(1), { once: true })

    },

    go(index) {
        if (!count) return
        this.current = (index + count) % count
        this.photo(this.current)
        this.photo(this.current + 1)
    },

    // Gives a slider photo its URL (they wait in data-src until needed).
    photo(index) {
        const img = this.$refs.gallery?.querySelector(`[data-photo="${index % count}"][data-src]`)
        if (!img) return
        if (img.dataset.srcset) img.srcset = img.dataset.srcset
        img.src = img.dataset.src
        img.removeAttribute('data-src')
    },

    // Loaded when the map comes near the screen (x-intersect in the view): Leaflet and its tiles are heavy.
    async map() {
        if (!latitude || !longitude) return
        const { default: L } = await import('leaflet')
        await import('leaflet/dist/leaflet.css')
        const map = L.map(this.$refs.map, { zoomControl: true, scrollWheelZoom: false }).setView([latitude, longitude], 14)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19,
        }).addTo(map)
        const icon = L.divIcon({
            className: '',
            html: '<div style="width:14px;height:14px;background:#c8102e;border:2.5px solid #fff;border-radius:50%;box-shadow:0 2px 8px rgba(0,0,0,0.35)"></div>',
            iconSize: [14, 14],
            iconAnchor: [7, 7],
        })
        const popup = document.createElement('strong')
        popup.textContent = place
        L.marker([latitude, longitude], { icon }).addTo(map).bindPopup(popup).openPopup()
    },

    // The 360° viewer (Pannellum), loaded when the visitor starts the tour: only needed then. Another view replaces it.
    async tour(index) {
        if (!tours[index]) return
        this.tourIndex = index
        this.touring = true
        await import('pannellum/build/pannellum.css')
        await import('pannellum/build/pannellum.js')
        this.viewer?.destroy()
        this.viewer = window.pannellum.viewer(this.$refs.pano, {
            type: 'equirectangular',
            panorama: tours[index],
            autoLoad: true,
            autoRotate: -2,
            compass: false,
            hfov: 100,
            strings: { loadingLabel: loading },
        })
    },

    similar() {
        const update = (sw) => (this.$refs.similarFill.style.width = sw.progress * 100 + '%')
        new Swiper(this.$refs.similar, {
            modules: [Navigation],
            navigation: { prevEl: this.$refs.similarPrev, nextEl: this.$refs.similarNext, disabledClass: 'is-disabled' },
            slidesPerView: 1,
            spaceBetween: 20,
            grabCursor: true,
            breakpoints: { 640: { slidesPerView: 2 }, 1024: { slidesPerView: 3 } },
            on: { init: update, slideChange: update, progress: update },
        })
    },

    pdf() {
        this.pdfOpen = true
    },
})
