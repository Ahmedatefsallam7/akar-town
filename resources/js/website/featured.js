// Home "Featured Properties" (the design's FEATURED PROPERTIES SLIDER): one split card; clicking a thumbnail
// flies a copy of its photo onto the main image while the details swap; autoplay every 4s (paused on hover);
// header and card reveal on scroll. Texts are Alpine bindings on `current`.
import { gsap } from 'gsap'

const AUTOPLAY_MS = 4000

export default (items) => ({
    items,
    idx: 0,
    busy: false,
    timer: null,

    get current() {
        return this.items[this.idx]
    },

    init() {
        const els = [...this.$el.querySelectorAll('[data-reveal-up]')]
        const observer = new IntersectionObserver(
            (entries) =>
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return
                    gsap.to(entry.target, { y: 0, opacity: 1, duration: 0.78, delay: +entry.target.dataset.revealDelay, ease: 'power3.out' })
                    observer.unobserve(entry.target)
                }),
            { threshold: 0.12 },
        )
        els.forEach((el, i) => {
            el.dataset.revealDelay = i * 0.1
            observer.observe(el)
        })

        if (this.items.length > 1) this.start()
    },

    start() {
        clearInterval(this.timer)
        if (this.items.length > 1) this.timer = setInterval(() => this.go((this.idx + 1) % this.items.length), AUTOPLAY_MS)
    },

    stop() {
        clearInterval(this.timer)
    },

    // Flying clone: from the clicked thumbnail onto the main image, while the details fade out and back in.
    go(newIdx) {
        if (this.busy || newIdx === this.idx) return
        this.busy = true

        const next = this.items[newIdx]
        const mainImg = this.$refs.main
        const info = this.$refs.info
        const tr = this.$root.querySelectorAll('[data-unit-thumb]')[newIdx].getBoundingClientRect()
        const mr = this.$refs.mainWrap.getBoundingClientRect()

        const clone = document.createElement('img')
        clone.src = next.images[0] ?? ''
        if (next.srcset) {
            clone.srcset = next.srcset
            clone.sizes = this.$refs.main.sizes
        }
        clone.style.cssText = `position:fixed;top:${tr.top}px;left:${tr.left}px;width:${tr.width}px;height:${tr.height}px;object-fit:cover;border-radius:8px;z-index:9999;pointer-events:none;will-change:transform`
        document.body.appendChild(clone)

        gsap.to(mainImg, { opacity: 0, duration: 0.25, ease: 'power2.in' })
        gsap.to(info, { opacity: 0, y: -10, duration: 0.28, ease: 'power2.in' })

        gsap.to(clone, {
            top: mr.top,
            left: mr.left,
            width: mr.width,
            height: mr.height,
            borderRadius: '14px',
            duration: 0.72,
            ease: 'power3.inOut',
            onComplete: () => {
                this.idx = newIdx

                const reveal = () => {
                    gsap.set(mainImg, { opacity: 1 })
                    gsap.to(clone, { opacity: 0, duration: 0.28, ease: 'power2.out', onComplete: () => clone.remove() })
                    gsap.fromTo(info, { opacity: 0, y: 10 }, { opacity: 1, y: 0, duration: 0.48, ease: 'power3.out' })
                    this.busy = false
                }

                this.$nextTick(() => {
                    if (mainImg.complete && mainImg.naturalWidth > 0) reveal()
                    else {
                        mainImg.onload = reveal
                        mainImg.onerror = reveal
                    }
                })
            },
        })
    },

    prev() {
        if (this.idx > 0) this.go(this.idx - 1)
    },

    next() {
        if (this.idx < this.items.length - 1) this.go(this.idx + 1)
    },
})
