// Home "Prime Destinations" (the design's script): drag to scroll, autoplay every 3.2s (paused on hover / drag),
// prev / next by 338px, a progress bar, the blurred backdrop following the hovered card, and the scroll reveal.
import { gsap } from 'gsap'

const STEP = 338
const AUTOPLAY_MS = 3200

export default () => ({
    progress: 0,
    atStart: true,
    atEnd: false,
    timer: null,
    fade: null,

    init() {
        const wrap = this.$refs.wrap
        const rtl = () => document.documentElement.dir === 'rtl'

        let dragging = false
        let startX = 0
        let scrollStart = 0
        wrap.addEventListener('mousedown', (e) => {
            dragging = true
            startX = e.clientX
            scrollStart = wrap.scrollLeft
            wrap.style.cursor = 'grabbing'
            this.stop()
        })
        window.addEventListener('mousemove', (e) => {
            if (!dragging) return
            e.preventDefault()
            wrap.scrollLeft = scrollStart - (e.clientX - startX)
        })
        window.addEventListener('mouseup', () => {
            if (!dragging) return
            dragging = false
            wrap.style.cursor = ''
            this.start()
        })
        // A drag must not open the card under the pointer.
        wrap.addEventListener('click', (e) => Math.abs(wrap.scrollLeft - scrollStart) > 5 && e.preventDefault(), true)

        wrap.addEventListener('scroll', () => this.update(), { passive: true })
        wrap.addEventListener('mouseenter', () => this.stop())
        wrap.addEventListener('mouseleave', () => this.start())

        this.scrollNext = () => {
            const max = wrap.scrollWidth - wrap.clientWidth
            const atEnd = rtl() ? wrap.scrollLeft <= -(max - 2) : wrap.scrollLeft >= max - 2
            if (atEnd) wrap.scrollTo({ left: 0, behavior: 'smooth' })
            else wrap.scrollBy({ left: rtl() ? -STEP : STEP, behavior: 'smooth' })
        }
        this.scrollPrev = () => wrap.scrollBy({ left: rtl() ? STEP : -STEP, behavior: 'smooth' })

        const reveal = [...this.$el.querySelectorAll('[data-loc-reveal]')]
        const observer = new IntersectionObserver(
            (entries) =>
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return
                    gsap.to(entry.target, { y: 0, opacity: 1, duration: 0.72, delay: reveal.indexOf(entry.target) * 0.07, ease: 'power3.out' })
                    observer.unobserve(entry.target)
                }),
            { threshold: 0.08 },
        )
        reveal.forEach((el) => observer.observe(el))

        this.update()
        this.start()
    },

    update() {
        const wrap = this.$refs.wrap
        const max = wrap.scrollWidth - wrap.clientWidth
        const position = Math.abs(wrap.scrollLeft)
        this.progress = max > 0 ? Math.min((position / max) * 100, 100) : 0
        this.atStart = position <= 0
        this.atEnd = position >= max - 2
    },

    start() {
        this.stop()
        this.timer = setInterval(() => this.scrollNext(), AUTOPLAY_MS)
    },

    stop() {
        clearInterval(this.timer)
    },

    prev() {
        this.stop()
        this.scrollPrev()
        this.start()
    },

    next() {
        this.stop()
        this.scrollNext()
        this.start()
    },

    // The section's blurred backdrop shows the hovered card's photo.
    ambient(image) {
        const el = this.$refs.ambient
        clearTimeout(this.fade)
        if (image) {
            el.style.backgroundImage = `url(${image})`
            el.classList.add('opacity-[0.16]')
            el.classList.remove('opacity-0')
        } else {
            this.fade = setTimeout(() => {
                el.classList.remove('opacity-[0.16]')
                el.classList.add('opacity-0')
            }, 200)
        }
    },
})
