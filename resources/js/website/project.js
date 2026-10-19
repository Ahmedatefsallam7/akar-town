// Project details page (the design's project-details.html scripts): hero parallax, unit type tabs, the gallery
// lightbox (counter, arrows, thumbnails, keys) and the PDF datasheet. Reveals use x-intersect in the view.
export default ({ count }) => ({
    tab: 0,
    gallery: false,
    index: 0,
    loaded: false,
    pdfLoading: false,

    init() {
        const bg = this.$refs.heroBg
        if (bg) {
            const onScroll = () => (bg.style.transform = `translateY(${window.scrollY * 0.28}px)`)
            window.addEventListener('scroll', onScroll, { passive: true })
            onScroll()
        }
    },

    open(index = 0) {
        this.go(index)
        this.gallery = true
        document.body.style.overflow = 'hidden'
        this.$nextTick(() => this.$refs.galleryClose?.focus())
    },

    close() {
        this.gallery = false
        document.body.style.overflow = ''
    },

    go(index) {
        if (!count) return
        const next = (index + count) % count
        if (next !== this.index) this.loaded = false
        this.index = next
        this.$nextTick(() => this.$refs.thumbs?.children[next]?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' }))
    },

    key(e) {
        if (!this.gallery) return
        if (e.key === 'Escape') this.close()
        if (e.key === 'ArrowLeft') this.go(this.index - 1)
        if (e.key === 'ArrowRight') this.go(this.index + 1)
    },

    async pdf() {
        if (this.pdfLoading) return
        this.pdfLoading = true
        try {
            const { default: buildPDF } = await import('./project-pdf')
            await buildPDF(this.$root)
        } finally {
            this.pdfLoading = false
        }
    },
})
