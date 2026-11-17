// Image protection (AKAR-51), on every website page from the first moment. The server side is the real lock (private
// files, short-lived links that don't open on their own, watermarks); this only makes the usual ways harder:
// - right-click, dragging and (CSS) long-press on an image do nothing;
// - Ctrl/Cmd+S ("save page") does nothing;
// - the images are blurred (data-unfocused) as soon as a screenshot shortcut starts (Cmd+Shift on a Mac, Win+Shift or
//   Print Screen on Windows), and while the page isn't focused (a screenshot tool opened over it, another app);
//   they come back on the next mouse move or touch on the page, which a screenshot tool's overlay holds back;
// - Print Screen empties what it copied (Windows), so the screenshot can't be pasted.
// Printing shows no images (print: classes on <body>). A phone's screenshot can't be seen by any website: the
// watermark covers that case.
const onImage = (target) => target instanceof Element && target.closest('img, picture')

document.addEventListener('contextmenu', (event) => onImage(event.target) && event.preventDefault())
document.addEventListener('dragstart', (event) => onImage(event.target) && event.preventDefault())

const blurImages = (state) => document.body?.toggleAttribute('data-unfocused', state)

// Back on the next sign of the visitor on the page, not before a short moment (the shortcut's own key presses).
let blurredAt = 0
const hide = () => {
    blurredAt = Date.now()
    blurImages(true)
}
const show = () => Date.now() - blurredAt > 800 && blurImages(false)

document.addEventListener('keydown', (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') event.preventDefault()
    if (event.key === 'PrintScreen' || (event.shiftKey && event.metaKey)) hide()
})

document.addEventListener('keyup', (event) => {
    if (event.key === 'PrintScreen') {
        hide()
        navigator.clipboard?.writeText('').catch(() => {})
    }
})

window.addEventListener('blur', hide)
document.addEventListener('visibilitychange', () => document.hidden && hide())
for (const type of ['mousemove', 'pointerdown', 'touchstart']) {
    document.addEventListener(type, show, { passive: true })
}
