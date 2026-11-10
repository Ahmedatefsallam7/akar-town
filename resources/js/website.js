// Public website entry: the page and its first photo are painted first, then the app (Livewire, Alpine and the page's
// components: website/boot.js) is fetched and started, so its start-up work doesn't hold back the first view. It
// starts right after the browser reports the first paint (and its biggest element), or after 1.5 s at the latest.
let started = false
const start = () => {
    if (started) return
    started = true
    import('./website/boot')
}

try {
    new PerformanceObserver((list, observer) => {
        observer.disconnect()
        // One more frame, so a photo finishing in the same paint is on screen too.
        requestAnimationFrame(() => setTimeout(start, 0))
    }).observe({ type: 'largest-contentful-paint', buffered: true })
} catch {
    // Browsers without paint timing (e.g. older Safari) just use the time limit.
}
setTimeout(start, 1500)
