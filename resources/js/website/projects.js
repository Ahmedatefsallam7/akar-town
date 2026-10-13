// Home "Our Projects" (the design's script): header rises in, each card is wiped in from its natural edge with its
// photo zooming out, and on hover a details panel opens from the card's edge (wide → from the left, tall → from the
// bottom, square → pops in) while the photo zooms and dims.
import { gsap } from 'gsap'

const WIPES = [
    { from: 'inset(0 100% 0 0 round 12px)', to: 'inset(0 0% 0 0 round 12px)', delay: 0 },
    { from: 'inset(0 0 100% 0 round 12px)', to: 'inset(0 0 0% 0 round 12px)', delay: 0.18 },
    { from: 'inset(100% 0 0 0 round 12px)', to: 'inset(0% 0 0 0 round 12px)', delay: 0.36 },
    { from: 'inset(100% 0 0 0 round 12px)', to: 'inset(0% 0 0 0 round 12px)', delay: 0.46 },
]

const HIDDEN = {
    landscape: { x: 0, y: 0, opacity: 1, scale: 1, clipPath: 'inset(0% 100% 0% 0%)' },
    portrait: { x: 0, y: 0, opacity: 1, scale: 1, clipPath: 'inset(100% 0% 0% 0%)' },
    square: { x: 0, y: 18, opacity: 0, scale: 0.91, clipPath: 'none' },
}

const typeOf = (card) => {
    const { width, height } = card.getBoundingClientRect()
    const ratio = width / height
    return ratio >= 1.35 ? 'landscape' : ratio <= 0.78 ? 'portrait' : 'square'
}

export default () => ({
    init() {
        const cards = [...this.$el.querySelectorAll('[data-card]')]

        const header = [...this.$el.querySelectorAll('[data-grid-reveal]')]
        const headerObserver = new IntersectionObserver(
            (entries) =>
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return
                    gsap.to(entry.target, { y: 0, opacity: 1, duration: 0.72, delay: header.indexOf(entry.target) * 0.06, ease: 'power3.out' })
                    headerObserver.unobserve(entry.target)
                }),
            { threshold: 0.08 },
        )
        header.forEach((el) => headerObserver.observe(el))

        const wipeObserver = new IntersectionObserver(
            (entries) => {
                if (!entries[0].isIntersecting) return
                wipeObserver.disconnect()
                cards.forEach((card, i) => {
                    const wipe = WIPES[i] || WIPES[WIPES.length - 1]
                    gsap.fromTo(card, { clipPath: wipe.from, opacity: 1 }, { clipPath: wipe.to, duration: 1.05, delay: wipe.delay, ease: 'power2.inOut' })
                    gsap.fromTo(card.querySelector('[data-img]'), { scale: 1.15 }, { scale: 1, duration: 1.3, delay: wipe.delay, ease: 'power2.out' })
                })
            },
            { threshold: 0.12 },
        )
        wipeObserver.observe(this.$refs.grid)

        cards.forEach((card) => this.hover(card))

        let resize
        window.addEventListener('resize', () => {
            clearTimeout(resize)
            resize = setTimeout(() => cards.forEach((card) => this.reset(card)), 220)
        })
    },

    reset(card) {
        const type = typeOf(card)
        card.dataset.type = type
        const panel = card.querySelector('[data-panel]')
        const texts = panel.querySelectorAll('[data-text]')
        const cta = panel.querySelector('[data-card-cta]')
        gsap.killTweensOf([panel, ...texts, cta])
        gsap.set(texts, { opacity: 0, y: 14 })
        gsap.set(cta, { opacity: 0, y: 10 })
        gsap.set(panel, HIDDEN[type])
    },

    hover(card) {
        const img = card.querySelector('[data-img]')
        const dim = card.querySelector('[data-dim]')
        const panel = card.querySelector('[data-panel]')
        const texts = panel.querySelectorAll('[data-text]')
        const cta = panel.querySelector('[data-card-cta]')
        let tl = null
        this.reset(card)

        card.addEventListener('mouseenter', () => {
            tl?.kill()
            const type = typeOf(card)
            card.dataset.type = type
            tl = gsap.timeline()
            tl.to(img, { scale: 1.06, duration: 0.35, ease: 'power2.out' }, 0)
            tl.to(dim, { opacity: 1, duration: 0.25, ease: 'power2.out' }, 0.04)
            if (type === 'square') tl.to(panel, { opacity: 1, scale: 1, y: 0, duration: 0.28, ease: 'power3.out' }, 0.04)
            else tl.to(panel, { clipPath: 'inset(0% 0% 0% 0%)', duration: 0.32, ease: 'power3.out' }, 0.04)
            tl.to(texts, { opacity: 1, y: 0, stagger: 0.04, duration: 0.22, ease: 'power2.out' }, 0.12)
            tl.to(cta, { opacity: 1, y: 0, duration: 0.18, ease: 'power2.out' }, '+=0.02')
        })

        card.addEventListener('mouseleave', () => {
            tl?.kill()
            const type = typeOf(card)
            tl = gsap.timeline()
            tl.to([...texts, cta], { opacity: 0, y: 8, duration: 0.12, ease: 'power2.in' }, 0)
            tl.to(img, { scale: 1, duration: 0.3, ease: 'power2.out' }, 0)
            tl.to(dim, { opacity: 0, duration: 0.2, ease: 'power2.in' }, 0)
            if (type === 'square') tl.to(panel, { opacity: 0, scale: 0.93, y: 12, duration: 0.22, ease: 'power2.in' }, 0.03)
            else tl.to(panel, { clipPath: type === 'landscape' ? 'inset(0% 100% 0% 0%)' : 'inset(100% 0% 0% 0%)', duration: 0.26, ease: 'power2.in' }, 0.03)
        })
    },

    // The favourite button's little bounce.
    pop(el) {
        gsap.fromTo(el, { scale: 0.7 }, { scale: 1, duration: 0.4, ease: 'elastic.out(1,0.5)' })
    },
})
