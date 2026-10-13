// Public website: Alpine components (Alpine comes with Livewire's scripts) and the design's GSAP motion.
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import featuredUnits from './website/featured'
import heroSlider from './website/hero'
import locationsSlider from './website/locations'
import projectsGrid from './website/projects'
import splashScreen from './website/splash'
import whyChoose from './website/why'

gsap.registerPlugin(ScrollTrigger)

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine

    // Records a call / WhatsApp / form click as a "possible lead" (admin's Possible Leads), e.g.
    // @click="$track('whatsapp', { unit_id: 5 })". keepalive: the request survives leaving the page (tel:, wa.me).
    Alpine.magic('track', () => (type, ids = {}) =>
        fetch(document.querySelector('meta[name="track-url"]').content, {
            method: 'POST',
            keepalive: true,
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ click_type: type, page_url: location.href, ...ids }),
        }).catch(() => {}),
    )

    Alpine.data('heroSlider', heroSlider)
    Alpine.data('featuredUnits', featuredUnits)
    Alpine.data('whyChoose', whyChoose)
    Alpine.data('projectsGrid', projectsGrid)
    Alpine.data('locationsSlider', locationsSlider)
    Alpine.data('splashScreen', splashScreen)

    // Light / dark theme: <html data-theme>, remembered in a cookie so the server renders the right theme (no flash).
    Alpine.store('theme', {
        dark: document.documentElement.dataset.theme === 'dark',
        toggle() {
            this.dark = !this.dark
            document.documentElement.dataset.theme = this.dark ? 'dark' : 'light'
            document.cookie = `theme=${this.dark ? 'dark' : 'light'};path=/;max-age=31536000;samesite=lax`
        },
    })

    // Remembered in the browser (as in the design) until accounts keep them.
    const read = (key) => {
        try {
            return JSON.parse(localStorage.getItem(key)) || []
        } catch {
            return []
        }
    }
    const write = (key, value) => {
        try {
            localStorage.setItem(key, JSON.stringify(value))
        } catch {}
    }

    // Compare list (up to 4 properties), shown in the compare bar on every page.
    Alpine.store('compare', {
        max: 4,
        items: read('akar_cmp'),
        open: false,
        has(id) {
            return this.items.some((item) => item.id === id)
        },
        toggle(item) {
            if (this.has(item.id)) return this.remove(item.id)
            if (this.items.length >= this.max) {
                window.dispatchEvent(new CustomEvent('compare-full'))
                return
            }
            this.items.push(item)
            this.open = true
            write('akar_cmp', this.items)
        },
        remove(id) {
            this.items = this.items.filter((item) => item.id !== id)
            if (!this.items.length) this.open = false
            write('akar_cmp', this.items)
        },
    })

    // Favourite properties.
    Alpine.store('favourites', {
        ids: read('akar_favs'),
        has(id) {
            return this.ids.includes(id)
        },
        toggle(id) {
            this.ids = this.has(id) ? this.ids.filter((fav) => fav !== id) : [...this.ids, id]
            write('akar_favs', this.ids)
        },
    })

    // Photo lightbox (the design's gallery lightbox), opened with a list of image URLs.
    Alpine.store('lightbox', {
        open: false,
        images: [],
        index: 0,
        show(images, index = 0) {
            this.images = images
            this.index = index
            this.open = true
            document.body.style.overflow = 'hidden'
        },
        close() {
            this.open = false
            document.body.style.overflow = ''
        },
        prev() {
            if (this.index > 0) this.index--
        },
        next() {
            if (this.index < this.images.length - 1) this.index++
        },
    })

    // The "Our Projects" lightbox (a second design: title + counter under the image).
    Alpine.store('projectLightbox', {
        open: false,
        images: [],
        index: 0,
        title: '',
        show(images, title) {
            Object.assign(this, { images, title, index: 0, open: true })
            document.body.style.overflow = 'hidden'
        },
        close() {
            this.open = false
            document.body.style.overflow = ''
        },
        prev() {
            if (this.index > 0) this.index--
        },
        next() {
            if (this.index < this.images.length - 1) this.index++
        },
    })

    // Shared with the footer spacer in the layout (no scroll range when the footer is static).
    Alpine.store('footer', { static: false })

    // The footer sits fixed behind the page and is revealed as the last screen scrolls away (the design's GSAP
    // ScrollTrigger timeline). When its content is taller than the screen it falls back to a normal static footer.
    Alpine.data('footerReveal', (reveal = true) => ({
        timeline: null,

        get static() {
            return Alpine.store('footer').static
        },

        set static(value) {
            Alpine.store('footer').static = value
        },

        init() {
            if (!reveal) {
                // A normal footer (every page but home): only the wordmark is fitted.
                this.fitMark()
                document.fonts?.ready.then(() => this.fitMark())
                window.addEventListener('resize', () => this.fitMark())
                return
            }

            this.layout()
            this.setupScroll()
            document.fonts?.ready.then(() => this.relayout())

            let frame
            window.addEventListener('resize', () => {
                cancelAnimationFrame(frame)
                frame = requestAnimationFrame(() => this.relayout())
            })
        },

        // Fit the "AKAR TOWN" wordmark to the full width (at most a third of the screen height).
        fitMark() {
            const { mark, markText } = this.$refs
            const probe = 200
            markText.style.fontSize = probe + 'px'
            const probeWidth = markText.getBoundingClientRect().width
            if (!probeWidth) return
            const fit = (mark.getBoundingClientRect().width / probeWidth) * probe
            markText.style.fontSize = Math.min(fit, window.innerHeight * 0.34) + 'px'
        },

        // Height of everything but the elastic wordmark.
        contentHeight() {
            const inner = this.$refs.inner
            const style = getComputedStyle(inner)
            let total = parseFloat(style.paddingTop) + parseFloat(style.paddingBottom)
            for (const child of inner.children) {
                if (child === this.$refs.mark) continue
                const childStyle = getComputedStyle(child)
                total += child.offsetHeight + parseFloat(childStyle.marginTop) + parseFloat(childStyle.marginBottom)
            }
            return total
        },

        layout() {
            this.fitMark()
            const shouldBeStatic = this.contentHeight() > window.innerHeight - (this.static ? 40 : 0)
            if (shouldBeStatic === this.static) return false
            this.static = shouldBeStatic
            this.$nextTick(() => this.fitMark())
            return true
        },

        relayout() {
            if (!this.layout()) return
            this.$nextTick(() => {
                this.setupScroll()
                ScrollTrigger.refresh()
            })
        },

        setupScroll() {
            const page = document.getElementById('site-wrap')
            const spacer = document.getElementById('footer-spacer')
            const targets = [...this.$el.querySelectorAll('[data-reveal]')]

            this.timeline?.scrollTrigger?.kill()
            this.timeline?.kill()
            this.timeline = null
            gsap.set([page, this.$refs.bg, ...targets], { clearProps: 'transform,opacity' })

            if (this.static) {
                gsap.set(targets, { opacity: 1, y: 0 })
                return
            }

            gsap.set(page, { transformOrigin: '50% 0%' })
            this.timeline = gsap
                .timeline({ scrollTrigger: { trigger: spacer, start: 'top bottom', end: 'bottom bottom', scrub: 0.6 } })
                .to(page, { yPercent: -6, scale: 0.95, ease: 'none' }, 0)
                .to(this.$refs.bg, { scale: 1, ease: 'none' }, 0)
                .from(targets, { opacity: 0, y: 32, stagger: 0.07, ease: 'power2.out' }, 0.08)
        },
    }))
})
