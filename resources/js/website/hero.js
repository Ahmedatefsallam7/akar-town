// Home hero (the design's index.html "HERO SLIDER": Swiper + GSAP liquid displacement wipe, thumbnails, progress,
// magnetic buttons, entry animation and the pointer-following preview deck). The slides are rendered by Blade;
// this drives them. Ported from the design's script as-is; only the DOM lookups and the progress fill changed.
import { gsap } from 'gsap'
import { CustomEase } from 'gsap/CustomEase'
import Swiper from 'swiper'
import 'swiper/css'

gsap.registerPlugin(CustomEase)
CustomEase.create('text.in', '0.16, 1, 0.3, 1')
CustomEase.create('smooth', '0.76, 0, 0.24, 1')

const AUTOPLAY_MS = 4500
const WIPE_MS = 1300
const REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches
const TEXT = '[data-text]'

export default (slides) => ({
    active: 0,

    init() {
        const root = this.$el
        const $ = (selector) => root.querySelector(selector)
        const $$ = (selector) => [...root.querySelectorAll(selector)]

        const stage = $('[data-stage]')
        const thumbsWrap = $('[data-thumbs]')
        const progCurrent = $('[data-prog-current]')
        const progFill = $('[data-prog-fill]')
        const announcer = $('[data-announcer]')

        let activeIdx = 0
        let isAnimating = false
        let autoTimer = null
        let progTween = null
        let swiper = null
        let previews = null

        // The other slides' photos wait until the page has loaded (or the first slide change), so the first photo
        // and the page come first.
        const loadPhotos = () =>
            $$('[data-bg-src]').forEach((bg) => {
                bg.style.setProperty('--bg', `url('${bg.dataset.bgSrc}')`)
                bg.style.setProperty('--bg-small', `url('${bg.dataset.bgSrcSmall}')`)
                bg.removeAttribute('data-bg-src')
            })
        if (document.readyState === 'complete') loadPhotos()
        else window.addEventListener('load', loadPhotos, { once: true })

        const getSlide = (i) => $(`[data-slide="${i}"]`)
        const getThumb = (i) => $(`[data-thumb="${i}"]`)
        const pad = (n) => String(n + 1).padStart(2, '0')

        const announce = (text) => {
            announcer.textContent = ''
            requestAnimationFrame(() => (announcer.textContent = text))
        }

        /* Progress bar + autoplay. The design animated scaleX on a 0px-high fill (never visible): here the fill
           grows down the 1px track over each slide. */
        // Phones: the track is a short horizontal line (the design's ≤768px row), so the fill grows sideways.
        const startProgBar = () => {
            progTween?.kill()
            const row = window.matchMedia('(max-width: 768px)').matches
            const axis = row ? 'scaleX' : 'scaleY'
            gsap.set(progFill, { scaleX: 1, scaleY: 1 })
            gsap.set(progFill, { [axis]: 0, transformOrigin: row ? (document.dir === 'rtl' ? 'right' : 'left') : 'top' })
            progTween = gsap.to(progFill, { [axis]: 1, duration: AUTOPLAY_MS / 1000, ease: 'none' })
        }
        const pauseProgBar = () => progTween?.pause()
        const clearAutoplay = () => {
            clearTimeout(autoTimer)
            autoTimer = null
            pauseProgBar()
        }
        const startAutoplay = () => {
            clearAutoplay()
            startProgBar()
            autoTimer = setTimeout(() => navigate((activeIdx + 1) % slides.length, false, true), AUTOPLAY_MS)
        }

        /* Text animations */
        const textIn = (slideEl) => {
            if (REDUCED) return
            const els = [...slideEl.querySelectorAll(TEXT)]
            gsap.killTweensOf(els)
            gsap.set(els, { opacity: 0, y: 28, filter: 'blur(10px)' })
            const tl = gsap.timeline()
            els.forEach((el, i) => tl.to(el, { opacity: 1, y: 0, filter: 'blur(0px)', duration: 0.78, ease: 'text.in' }, i * 0.11))
            return tl
        }
        const textOut = (slideEl) => {
            if (REDUCED) return
            const els = [...slideEl.querySelectorAll(TEXT)]
            gsap.killTweensOf(els)
            return gsap.to(els.reverse(), { opacity: 0, y: -16, filter: 'blur(6px)', duration: 0.35, ease: 'power2.in', stagger: 0.06 })
        }

        /* Liquid displacement wipe: the outgoing slide is clipped by a wavy diagonal seam sweeping across, with a
           canvas sheen on the seam; the new slide's text comes in at 62% of the wipe. */
        const doLiquidWipe = (fromIdx, toIdx, forward) => {
            const fromSlide = getSlide(fromIdx)
            const toSlide = getSlide(toIdx)
            if (!fromSlide || !toSlide) return

            let W = stage.offsetWidth
            let H = stage.offsetHeight
            const SKEW = 0.18
            const REVEAL_AT = 0.62
            const DIR = forward ? 1 : -1
            const PTS = 96

            gsap.set(toSlide, { zIndex: 1, display: 'block', visibility: 'visible' })
            gsap.set(fromSlide, { zIndex: 2 })

            const cnv = document.createElement('canvas')
            cnv.setAttribute('aria-hidden', 'true')
            cnv.width = W
            cnv.height = H
            cnv.style.cssText = 'position:absolute;inset:0;z-index:3;pointer-events:none;mix-blend-mode:screen;'
            stage.appendChild(cnv)
            const ctx = cnv.getContext('2d')

            loadPhotos()
            const nextSlide = slides[(toIdx + 1) % slides.length]
            new Image().src = window.matchMedia('(max-width: 768px)').matches ? nextSlide.imageSmall : nextSlide.image

            const fromBg = fromSlide.querySelector('[data-bg]')
            const toBg = toSlide.querySelector('[data-bg]')
            const state = { p: 0 }
            let clock = 0
            let lastTs = performance.now()
            let revealed = false

            textOut(fromSlide)

            const tl = gsap.timeline({
                onComplete() {
                    cnv.remove()
                    fromSlide.style.clipPath = ''
                    if (fromBg) fromBg.style.transform = ''
                    if (toBg) toBg.style.transform = ''
                    gsap.set(fromSlide, { display: 'none', visibility: 'hidden', zIndex: 0 })
                    gsap.set(toSlide, { zIndex: 2 })
                    isAnimating = false
                    if (!revealed) textIn(toSlide)
                    startAutoplay()
                },
            })

            if (REDUCED) {
                gsap.set(toSlide, { opacity: 0 })
                tl.to(fromSlide, { opacity: 0, duration: 0.4 })
                    .to(toSlide, { opacity: 1, duration: 0.4 }, 0)
                    .add(() => gsap.set(fromSlide, { opacity: 1 }))
                return
            }

            const getSeamX = (ty, p, t) => {
                const waveAmp = Math.sin(p * Math.PI)
                const w1 = Math.sin(ty * 1.6 * Math.PI + t * 0.5) * 0.06
                const w2 = Math.sin(ty * 3.2 * Math.PI - t * 0.8 + 1.57) * 0.028
                const seamUv = 1.15 - p * 1.3 + (ty - 0.5) * SKEW + (w1 + w2) * waveAmp
                return DIR === 1 ? seamUv * W : (1 - seamUv) * W
            }

            const renderFrame = (p, t) => {
                W = stage.offsetWidth
                H = stage.offsetHeight
                if (cnv.width !== W || cnv.height !== H) {
                    cnv.width = W
                    cnv.height = H
                } else ctx.clearRect(0, 0, W, H)

                const waveAmp = Math.sin(p * Math.PI)
                const pts = []
                for (let i = 0; i <= PTS; i++) {
                    const ty = i / PTS
                    pts.push([getSeamX(ty, p, t), ty * H])
                }

                const edge = DIR === 1 ? 0 : W
                const poly = [[edge, 0], ...pts, [edge, H]]
                fromSlide.style.clipPath = 'polygon(' + poly.map(([x, y]) => `${x.toFixed(1)}px ${y.toFixed(1)}px`).join(',') + ')'

                if (fromBg) fromBg.style.transform = `translateX(${((DIR === 1 ? -1 : 1) * waveAmp * 0.06 * W).toFixed(2)}px)`
                if (toBg) toBg.style.transform = `translateX(${(waveAmp * 0.03 * DIR * W).toFixed(2)}px)`

                if (waveAmp < 0.005) return

                const makeWave = (uvOff, phShift) => {
                    const arr = []
                    for (let i = 0; i <= PTS; i++) {
                        const ty = i / PTS
                        const w1 = Math.sin(ty * 1.6 * Math.PI + t * 0.5 + phShift) * 0.06 * waveAmp
                        const w2 = Math.sin(ty * 3.2 * Math.PI - t * 0.8 + 1.57 + phShift * 0.65) * 0.028 * waveAmp
                        const uv = 1.15 - p * 1.3 + uvOff + (ty - 0.5) * SKEW + w1 + w2
                        arr.push([DIR === 1 ? uv * W : (1 - uv) * W, ty * H])
                    }
                    return arr
                }

                const drawWave = (arr, alpha, outerW, midW) => {
                    const path = () => {
                        ctx.beginPath()
                        ctx.moveTo(arr[0][0], arr[0][1])
                        for (let i = 1; i < arr.length; i++) ctx.lineTo(arr[i][0], arr[i][1])
                    }
                    ctx.save()
                    ctx.lineJoin = 'round'
                    ctx.lineCap = 'round'
                    path()
                    ctx.strokeStyle = `rgba(255,255,255,${(alpha * 0.05).toFixed(3)})`
                    ctx.lineWidth = outerW
                    ctx.stroke()
                    path()
                    ctx.strokeStyle = `rgba(255,255,255,${(alpha * 0.14).toFixed(3)})`
                    ctx.lineWidth = midW
                    ctx.stroke()
                    ctx.restore()
                }

                drawWave(makeWave(-0.26, 1.4), waveAmp * 0.28, 22, 8)
                drawWave(makeWave(-0.17, 0.9), waveAmp * 0.44, 30, 10)
                drawWave(makeWave(-0.09, 0.45), waveAmp * 0.62, 40, 13)
                drawWave(pts, waveAmp * 0.8, 56, 18)
            }

            tl.to(state, {
                p: 1,
                duration: WIPE_MS / 1000,
                ease: 'power2.inOut',
                onUpdate() {
                    const now = performance.now()
                    clock += (now - lastTs) * 0.001
                    lastTs = now
                    if (!revealed && state.p >= REVEAL_AT) {
                        revealed = true
                        textIn(toSlide)
                    }
                    renderFrame(state.p, clock)
                },
            })
        }

        /* forward: true = next, false = previous, null = from the index difference */
        const navigate = (toIdx, fromExternal = false, forward = null) => {
            if (isAnimating || toIdx === activeIdx) return
            isAnimating = true

            const fromIdx = activeIdx
            activeIdx = toIdx
            this.active = toIdx
            const goForward = forward !== null ? forward : toIdx > fromIdx

            const toThumb = getThumb(toIdx)
            if (toThumb) {
                thumbsWrap.scrollTo({ top: toThumb.offsetTop - thumbsWrap.clientHeight / 2 + toThumb.offsetHeight / 2, behavior: 'smooth' })
            }

            progCurrent.textContent = pad(toIdx)
            announce(slides[toIdx].announce)
            if (swiper && !fromExternal) swiper.slideTo(toIdx, 0, false)
            clearAutoplay()
            doLiquidWipe(fromIdx, toIdx, goForward)
            previews?.setActive(toIdx)
        }
        this.navigate = navigate

        swiper = new Swiper($('[data-swiper]'), {
            virtualTranslate: true,
            speed: 0,
            allowTouchMove: true,
            threshold: 20,
            longSwipesRatio: 0.2,
            keyboard: { enabled: false },
            on: {
                init(sw) {
                    sw.slides.forEach((sl, i) =>
                        gsap.set(sl, i === 0 ? { zIndex: 2, display: 'block', visibility: 'visible' } : { zIndex: 0, display: 'none', visibility: 'hidden' }),
                    )
                },
                slideChangeTransitionStart(sw) {
                    if (!isAnimating) navigate(sw.activeIndex, true, null)
                },
                setTranslate(sw, translate) {
                    if (isAnimating) return
                    const bg = sw.slides[activeIdx]?.querySelector('[data-bg]')
                    if (bg) gsap.set(bg, { x: translate * 0.08 })
                },
                touchEnd(sw) {
                    const bg = sw.slides[activeIdx]?.querySelector('[data-bg]')
                    if (bg) gsap.to(bg, { x: 0, duration: 0.5, ease: 'smooth' })
                },
            },
        })

        this.prev = () => navigate((activeIdx - 1 + slides.length) % slides.length, false, false)
        this.next = () => navigate((activeIdx + 1) % slides.length, false, true)

        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') this.prev()
            if (e.key === 'ArrowRight') this.next()
        })

        stage.addEventListener('mouseenter', clearAutoplay)
        stage.addEventListener('mouseleave', startAutoplay)
        stage.addEventListener('touchstart', clearAutoplay, { passive: true })
        stage.addEventListener('touchend', startAutoplay, { passive: true })

        // Magnetic prev / next buttons.
        $$('[data-nav-btn]').forEach((btn) => {
            btn.addEventListener('mousemove', (e) => {
                const r = btn.getBoundingClientRect()
                gsap.to(btn, { x: (e.clientX - r.left - r.width / 2) * 0.35, y: (e.clientY - r.top - r.height / 2) * 0.35, duration: 0.3, ease: 'power2.out', overwrite: true })
            })
            btn.addEventListener('mouseleave', () => gsap.to(btn, { x: 0, y: 0, duration: 0.5, ease: 'elastic.out(1,0.4)', overwrite: true }))
        })

        /* Entry animation (after the design's 500ms loader pause) */
        const entryAnimation = () => {
            const firstSlide = getSlide(0)
            const tl = gsap.timeline({
                onComplete() {
                    textIn(firstSlide)
                    startAutoplay()
                },
            })

            const navbar = document.querySelector('nav')
            gsap.set(navbar, { y: -20, opacity: 0 })
            tl.to(navbar, { y: 0, opacity: 1, duration: 0.7, ease: 'smooth' }, 0)

            const bg = firstSlide.querySelector('[data-bg]')
            gsap.set(bg, { scale: 1.08 })
            tl.to(bg, { scale: 1, duration: 1.6, ease: 'smooth' }, 0.3)

            const thumbItems = $$('[data-thumb]')
            gsap.set(thumbItems, { x: -20, opacity: 0 })
            tl.to(thumbItems, { x: 0, opacity: 1, duration: 0.6, ease: 'smooth', stagger: 0.08 }, 0.5)

            const glassTag = $('[data-glass-tag]')
            gsap.set(glassTag, { y: -10, opacity: 0 })
            tl.to(glassTag, { y: 0, opacity: 1, duration: 0.6, ease: 'smooth' }, 0.8)

            const progEls = $$('[data-prog]')
            gsap.set(progEls, { opacity: 0 })
            tl.to(progEls, { opacity: 1, duration: 0.5, stagger: 0.1 }, 0.9)

            const navBtns = $$('[data-nav-btn]')
            gsap.set(navBtns, { scale: 0.8, opacity: 0 })
            tl.to(navBtns, { scale: 1, opacity: 1, duration: 0.5, ease: 'back.out(1.4)', stagger: 0.1 }, 1.0)
        }

        // Before the entry animation runs, keep the first slide's text hidden (it animates in).
        if (!REDUCED) gsap.set(getSlide(0).querySelectorAll(TEXT), { opacity: 0 })
        setTimeout(entryAnimation, 500)

        previews = previewDeck(root, slides, () => activeIdx)
    },
})

/* Preview deck: thumbnail cards that dock on the thumbnails at rest and trail the pointer across the hero while
   hovering (the design's "HERO PREVIEW DECK"). Only for a fine pointer. */
function previewDeck(heroEl, slides, getActive) {
    if (REDUCED || !window.matchMedia('(hover: hover) and (pointer: fine)').matches) return null

    const layer = heroEl.querySelector('[data-previews]')
    const PREVIEW_MAX = 5
    const FOLLOW_LEAD = 0.17
    const FOLLOW_FALLOFF = 0.19
    const EASE_FLOOR = 0.28
    const DOCK_LEAD = 0.19
    const DOCK_FALLOFF = 0.06
    const OPACITY_GAIN = 1.8
    const FAN_X = 18
    const FAN_Y = 14
    const FAN_ROT = -1.7
    const FLOAT_SCALE = 0.94
    const FLOAT_OPACITY = 0.86
    const DOCK_OPACITY = 0.52
    const SETTLED = 0.25

    const count = Math.min(slides.length, PREVIEW_MAX)
    let windowStart = 0
    let activeLocal = 0
    let mode = 'dock'
    let heroInside = false
    let introPlayed = false
    const client = { x: 0, y: 0 }
    const pointer = { x: 0, y: 0 }
    const base = { left: 0, top: 0 }
    let slotPoints = []
    let raf = 0
    let last = 0
    const zonesIn = new Set()

    const restZones = [heroEl.querySelector('[data-thumbs]'), ...heroEl.querySelectorAll('[data-ctas]')]

    const cards = [...layer.querySelectorAll('[data-preview]')].slice(0, count).map((el) => ({
        el,
        img: el.querySelector('img'),
        x: 0,
        y: 0,
        scale: 1,
        rot: 0,
        op: 0,
        in: { x: 0, y: 0, rot: 0, scale: 1, op: 1 },
    }))

    const readBase = () => {
        const r = layer.getBoundingClientRect()
        base.left = r.left
        base.top = r.top
    }
    const readPointer = () => {
        pointer.x = client.x - base.left
        pointer.y = client.y - base.top
    }
    const getWindowStart = () => {
        const spare = slides.length - count
        if (spare <= 0) return 0
        return Math.min(Math.max(activeLocal - Math.floor((count - 1) / 2), 0), spare)
    }
    const measureSlots = () => {
        const slotEls = [...heroEl.querySelectorAll('[data-thumb]')]
        readBase()
        const cardH = cards[0]?.el.offsetHeight || 74
        slotPoints = cards.map((_, i) => {
            const el = slotEls[windowStart + i]
            if (!el) return { x: 0, y: 0 }
            const r = el.getBoundingClientRect()
            return { x: r.left - base.left + r.width / 2, y: r.top - base.top + cardH / 2 }
        })
    }
    const syncWindow = (force = false) => {
        const start = getWindowStart()
        if (!force && start === windowStart) return
        windowStart = start
        cards.forEach((card, i) => {
            const slide = slides[start + i]
            if (slide && card.img.getAttribute('src') !== slide.thumb) card.img.setAttribute('src', slide.thumb)
        })
        if (!force) measureSlots()
    }
    const paint = (card) => {
        const e = card.in
        card.el.style.transform =
            `translate3d(${(card.x + e.x).toFixed(2)}px,${(card.y + e.y).toFixed(2)}px,0)` +
            ` scale(${(card.scale * e.scale).toFixed(3)}) rotate(${(card.rot + e.rot).toFixed(2)}deg)`
        card.el.style.opacity = (card.op * e.op).toFixed(3)
    }
    const targetFor = (i, m = mode) => {
        const isActive = windowStart + i === activeLocal
        let t
        if (m === 'dock') {
            const p = slotPoints[i] || { x: 0, y: 0 }
            t = { x: p.x, y: p.y, scale: 1, rot: 0, op: isActive ? 1 : DOCK_OPACITY }
        } else {
            t = { x: pointer.x + i * FAN_X, y: pointer.y + i * FAN_Y, scale: FLOAT_SCALE, rot: i * FAN_ROT, op: isActive ? 1 : FLOAT_OPACITY }
        }
        if (!heroInside || zonesIn.size) t.op = 0
        return t
    }
    const ease = (i) => {
        const docked = mode === 'dock'
        return (docked ? DOCK_LEAD : FOLLOW_LEAD) * Math.max(1 - i * (docked ? DOCK_FALLOFF : FOLLOW_FALLOFF), EASE_FLOOR)
    }
    const seat = () => {
        measureSlots()
        mode = 'dock'
        cards.forEach((card, i) => {
            const t = targetFor(i, 'dock')
            Object.assign(card, { x: t.x, y: t.y, scale: t.scale, rot: t.rot, op: 0 })
            paint(card)
        })
    }
    const tick = (now) => {
        const frames = last ? Math.min((now - last) / 16.667, 4) : 1
        last = now
        readBase()
        readPointer()
        let moving = false
        cards.forEach((card, i) => {
            const t = targetFor(i)
            const e = ease(i)
            const k = 1 - Math.pow(1 - e, frames)
            const ko = 1 - Math.pow(1 - Math.min(e * OPACITY_GAIN, 0.75), frames)
            card.x += (t.x - card.x) * k
            card.y += (t.y - card.y) * k
            card.scale += (t.scale - card.scale) * k
            card.rot += (t.rot - card.rot) * k
            card.op += (t.op - card.op) * ko
            if (
                Math.abs(t.x - card.x) > SETTLED ||
                Math.abs(t.y - card.y) > SETTLED ||
                Math.abs(t.op - card.op) > 0.004 ||
                Math.abs(t.rot - card.rot) > 0.05 ||
                Math.abs(t.scale - card.scale) > 0.002
            ) {
                moving = true
            } else {
                Object.assign(card, { x: t.x, y: t.y, scale: t.scale, rot: t.rot, op: t.op })
            }
            paint(card)
        })
        if (!moving) {
            raf = 0
            last = 0
            return
        }
        raf = requestAnimationFrame(tick)
    }
    const startLoop = () => {
        if (raf) return
        last = 0
        raf = requestAnimationFrame(tick)
    }
    const setMode = (next) => {
        if (mode === next) return
        if (next === 'dock') measureSlots()
        mode = next
        startLoop()
    }
    const measure = () => {
        measureSlots()
        startLoop()
    }
    const intro = () => {
        measureSlots()
        const centre = slotPoints.reduce((s, p) => s + p.x, 0) / (slotPoints.length || 1)
        const middle = (cards.length - 1) / 2
        cards.forEach((card, i) => {
            const p = slotPoints[i] || { x: centre }
            Object.assign(card.in, { x: (centre - p.x) * 0.66, y: 26 + i * 3, rot: (i - middle) * -3.2, scale: 0.82 - i * 0.03, op: 0 })
            paint(card)
        })
        const offsets = cards.map((c) => c.in)
        gsap.timeline({ onUpdate: () => cards.forEach(paint), onComplete: measure })
            .to(offsets, { x: 0, y: 0, rot: 0, scale: 1, duration: 0.9, ease: 'expo.out', stagger: 0.1 }, 0)
            .to(offsets, { op: 1, duration: 0.55, ease: 'power2.out', stagger: 0.1 }, 0)
    }
    const setActive = (index) => {
        activeLocal = index
        syncWindow()
        cards.forEach((card, i) => {
            const active = windowStart + i === index
            card.el.classList.toggle('outline-white/75', active)
            card.el.classList.toggle('outline-white/14', !active)
        })
        startLoop()
    }

    heroEl.addEventListener('pointerenter', () => {
        heroInside = true
        if (!introPlayed) {
            introPlayed = true
            intro()
        } else startLoop()
    })
    heroEl.addEventListener('pointermove', (e) => {
        client.x = e.clientX
        client.y = e.clientY
        if (!raf) readBase()
        readPointer()
        if (!zonesIn.size) setMode('float')
        startLoop()
    })
    heroEl.addEventListener('pointerleave', () => {
        heroInside = false
        setMode('dock')
        startLoop()
    })
    restZones.forEach((zone) => {
        zone.addEventListener('pointerenter', (e) => {
            zonesIn.add(e.currentTarget)
            setMode('dock')
            startLoop()
        })
        zone.addEventListener('pointerleave', (e) => {
            zonesIn.delete(e.currentTarget)
            startLoop()
        })
    })
    let resizeRaf
    window.addEventListener('resize', () => {
        cancelAnimationFrame(resizeRaf)
        resizeRaf = requestAnimationFrame(measure)
    })

    syncWindow(true)
    setActive(getActive())
    seat()

    return { setActive }
}
