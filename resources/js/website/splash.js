// Home splash screen (the design's script, shortened on request: the design waits 3s / 4.5s): the sketch columns
// race upward every frame, the logo shows at 1.2s and the panels part at 2.2s (or on click / Escape); the splash is
// removed 800ms after it starts leaving.
const SPEEDS = [27.0, 21.6, 31.5, 23.4, 28.8]
const LOGO_AT = 1000
const EXIT_AT = 2000
const CARD_HEIGHT = 234
const CARDS_PER_COLUMN = 8

export default () => ({
    state: 'start',
    gone: false,
    raf: null,
    timers: [],

    init() {
        const columns = [...this.$el.querySelectorAll('[data-column]')]
        const total = CARD_HEIGHT * CARDS_PER_COLUMN
        const offsets = columns.map(() => 0)
        const tick = () => {
            columns.forEach((column, i) => {
                offsets[i] = (offsets[i] + SPEEDS[i]) % total
                column.style.transform = `translateY(-${offsets[i]}px)`
            })
            this.raf = requestAnimationFrame(tick)
        }
        this.raf = requestAnimationFrame(tick)

        this.timers = [setTimeout(() => (this.state = 'ready'), LOGO_AT), setTimeout(() => this.exit(), EXIT_AT)]
    },

    exit() {
        if (this.state === 'exit') return
        this.timers.forEach(clearTimeout)
        cancelAnimationFrame(this.raf)
        this.state = 'exit'
        setTimeout(() => (this.gone = true), 800)
    },
})
