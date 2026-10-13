// Home "Why Choose Us" (the design's script): numbers count up when they show, the header / numbers rise in,
// the dotted path draws itself left to right, and the four stages fade up.
import { gsap } from 'gsap'

const observe = (elements, threshold, onShow) => {
    const observer = new IntersectionObserver(
        (entries) =>
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return
                onShow(entry.target)
                observer.unobserve(entry.target)
            }),
        { threshold },
    )
    elements.forEach((el) => observer.observe(el))
}

// 1800ms ease-out count; 1000 and up shown as "2K".
const countUp = (el) => {
    const target = parseInt(el.dataset.target, 10) || 0
    const suffix = el.dataset.suffix || ''
    const start = performance.now()
    const tick = (now) => {
        const progress = Math.min((now - start) / 1800, 1)
        const value = Math.round((1 - Math.pow(1 - progress, 3)) * target)
        el.textContent = (value >= 1000 ? (value / 1000).toFixed(1).replace(/\.0$/, '') + 'K' : value) + suffix
        if (progress < 1) requestAnimationFrame(tick)
    }
    requestAnimationFrame(tick)
}

export default () => ({
    init() {
        const root = this.$el

        observe(root.querySelectorAll('[data-stat]'), 0.4, (stat) => {
            stat.classList.add('after:opacity-100')
            countUp(stat.querySelector('[data-target]'))
        })

        const reveal = [...root.querySelectorAll('[data-why-reveal]')]
        reveal.forEach((el, i) => (el.dataset.whyDelay = i * 0.08))
        observe(reveal, 0.15, (el) => gsap.to(el, { y: 0, opacity: 1, duration: 0.72, delay: +el.dataset.whyDelay, ease: 'power3.out' }))

        const clip = root.querySelector('[x-ref="clip"]')
        observe([clip.closest('svg').parentElement], 0.2, () => gsap.to(clip, { attr: { width: 790 }, duration: 1.5, ease: 'none', delay: 0.2 }))

        observe(root.querySelectorAll('[data-stage]'), 0.2, (stage) => {
            stage.classList.remove('translate-y-[30px]', 'opacity-0')
            stage.classList.add('translate-y-0', 'opacity-100')
        })
    },
})
