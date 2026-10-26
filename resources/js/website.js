// Public website: Alpine components (Alpine comes with Livewire's scripts) and the design's GSAP motion.
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import featuredUnits from './website/featured'
import heroSlider from './website/hero'
import locationsSlider from './website/locations'
import projectPage from './website/project'
import projectsGrid from './website/projects'
import propertyPage from './website/property'
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
    Alpine.data('propertyPage', propertyPage)
    Alpine.data('projectPage', projectPage)

    // Light / dark theme: <html data-theme>, remembered in a cookie so the server renders the right theme (no flash).
    Alpine.store('theme', {
        dark: document.documentElement.dataset.theme === 'dark',
        toggle() {
            this.dark = !this.dark
            document.documentElement.dataset.theme = this.dark ? 'dark' : 'light'
            document.cookie = `theme=${this.dark ? 'dark' : 'light'};path=/;max-age=31536000;samesite=lax`
        },
    })

    // Remembered in the browser (as in the design).
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
    const forget = (key) => {
        try {
            localStorage.removeItem(key)
        } catch {}
    }

    // Compare list, shown in the compare bar on every page. Its size comes from the server (CompareUnits::MAX).
    Alpine.store('compare', {
        max: Number(document.querySelector('meta[name="compare-max"]')?.content) || 4,
        // Items saved before properties had slugs can't be compared: dropped.
        items: read('akar_cmp').filter((item) => item && item.slug),
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

    // Favourites (the navbar's wishlist): unit ids, and projects as "project-<id>" (the design's "proj-" items).
    // A guest's stay in the browser. Signed in, they come from the account and each click is saved there; the
    // guest list is moved to the account once, then cleared from the browser (not left behind after signing out).
    const account = document.querySelector('meta[name="account-favourites"]')
    const save = (changes) =>
        fetch(account.dataset.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(changes),
        })
    Alpine.store('favourites', {
        ids: account ? JSON.parse(account.content) : read('akar_favs'),
        init() {
            if (!account) return
            const guest = read('akar_favs').filter((id) => !this.ids.includes(id))
            if (guest.length) this.ids = [...this.ids, ...guest]
            if (!guest.length) return forget('akar_favs')
            save({ add: guest })
                .then((response) => response.ok && forget('akar_favs'))
                .catch(() => {})
        },
        has(id) {
            return this.ids.includes(id)
        },
        toggle(id) {
            const saved = this.has(id)
            this.ids = saved ? this.ids.filter((fav) => fav !== id) : [...this.ids, id]
            if (account) save(saved ? { remove: [id] } : { add: [id] }).catch(() => {})
            else write('akar_favs', this.ids)
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
    Alpine.store('footer', { static: false, atEnd: false })

    // The footer sits fixed behind the page and is revealed as the last screen scrolls away (the design's GSAP
    // ScrollTrigger timeline). When its content is taller than the screen it falls back to a normal static footer.
    Alpine.data('footerReveal', (reveal = true) => ({
        timeline: null,
        contentTween: null,

        get static() {
            return Alpine.store('footer').static
        },

        set static(value) {
            Alpine.store('footer').static = value
        },

        init() {
            this.lazyBackground()

            if (!reveal) {
                // A normal footer (every page but home): only the wordmark is fitted.
                this.fitMark()
                document.fonts?.ready.then(() => this.fitMark())
                window.addEventListener('resize', () => this.fitMark())
                return
            }

            this.watchEnd()
            this.layout()
            this.setupScroll()
            document.fonts?.ready.then(() => this.relayout())

            let frame
            window.addEventListener('resize', () => {
                cancelAnimationFrame(frame)
                frame = requestAnimationFrame(() => this.relayout())
            })
        },

        // The footer photo is fetched when the visitor is about a screen away from the footer, not with the page. On
        // home the footer sits fixed behind the page from the start, so the spacer that reveals it is watched instead.
        lazyBackground() {
            const bg = this.$refs.bg
            const target = (reveal && document.getElementById('footer-spacer')) || this.$el
            const observer = new IntersectionObserver(
                (entries) => {
                    if (!entries.some((entry) => entry.isIntersecting)) return
                    const small = window.matchMedia('(max-width: 768px)').matches
                    bg.style.backgroundImage = `url('${small ? bg.dataset.bgSmall : bg.dataset.bg}')`
                    observer.disconnect()
                },
                { rootMargin: '0px 0px 100% 0px' },
            )
            observer.observe(target)
        },

        watchEnd() {
            const spacer = document.getElementById('footer-spacer')
            if (!spacer) return

            const observer = new IntersectionObserver(([entry]) => {
                Alpine.store('footer').atEnd = entry.isIntersecting
            })
            observer.observe(spacer)
        },

        // Fit the "AKAR TOWN" wordmark to the full width (the design also capped it at a third of the screen height; the user
        // wants the full width). In the revealed footer it must also fit, whole, in the height left under the content: on a
        // screen too short for both it gets smaller rather than cut (short screens also hide two form labels to make room).
        fitMark() {
            const { mark, markText } = this.$refs
            const probe = 200
            markText.style.fontSize = probe + 'px'
            const probeBox = markText.getBoundingClientRect()
            if (!probeBox.width) return
            let size = (mark.getBoundingClientRect().width / probeBox.width) * probe
            if (reveal && !this.static) {
                size = Math.min(size, (mark.clientHeight / probeBox.height) * probe)
            }
            markText.style.fontSize = size + 'px'
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

            for (const animation of [this.timeline, this.contentTween]) {
                animation?.scrollTrigger?.kill()
                animation?.kill()
            }
            this.timeline = this.contentTween = null
            gsap.set([page, this.$refs.bg, ...targets], { clearProps: 'transform,opacity' })

            if (this.static) {
                gsap.set(targets, { opacity: 1, y: 0 })
                return
            }

            // The page slides up off the footer with the scroll (as in the design).
            gsap.set(page, { transformOrigin: '50% 0%' })
            this.timeline = gsap
                .timeline({ scrollTrigger: { trigger: spacer, start: 'top bottom', end: 'bottom bottom', scrub: 0.6 } })
                .to(page, { yPercent: -6, scale: 0.95, ease: 'none' }, 0)
                .to(this.$refs.bg, { scale: 1, ease: 'none' }, 0)

            // The footer's content plays in full once half of it shows (and back when scrolling up). The design ties it
            // to the scroll too, so stopping a little before the end left it faded, its columns at different heights.
            this.contentTween = gsap.from(targets, {
                opacity: 0,
                y: 32,
                duration: 0.6,
                stagger: 0.07,
                ease: 'power2.out',
                scrollTrigger: { trigger: spacer, start: 'top 50%', toggleActions: 'play none none reverse' },
            })
        },
    }))
})
