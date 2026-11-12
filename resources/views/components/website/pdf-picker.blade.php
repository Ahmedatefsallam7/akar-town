{{--
    The "Download PDF" language window (property and project pages): English or Arabic, each a download link to the
    datasheet built on the server. Shown while the page's Alpine "pdfOpen" is true; Escape, the × or a click outside close it.
--}}
@props(['title', 'label', 'en', 'ar'])

<div
    x-cloak
    x-show="pdfOpen"
    x-effect="document.body.style.overflow = pdfOpen ? 'hidden' : ''"
    x-transition.opacity
    @keydown.escape.window="pdfOpen = false"
    @click.self="pdfOpen = false"
    class="fixed inset-0 z-9998 flex items-center justify-center bg-[rgba(9,10,12,0.72)] p-4 backdrop-blur-[10px]"
    role="dialog"
    aria-modal="true"
    aria-labelledby="property-pdf-language-title"
>
    <div class="relative w-full max-w-[440px] overflow-hidden rounded-2xl border border-white/70 bg-paper text-ink shadow-[0_28px_90px_rgba(0,0,0,0.32)] dark:border-line dark:bg-surface-2 dark:text-ink">
        <div class="h-1 w-full bg-brand"></div>
        <div class="p-6 sm:p-7">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand/10 text-brand dark:bg-brand/15" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="size-[21px]"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/></svg>
                    </span>
                    <div class="pt-0.5">
                        <p class="mb-1 text-[9px] font-bold tracking-[0.12em] text-brand uppercase rtl:tracking-normal">{{ $label }}</p>
                        <h2 id="property-pdf-language-title" class="text-[19px] leading-tight font-semibold rtl:font-arabic">{{ __('Choose PDF language') }}</h2>
                        <p class="mt-1.5 max-w-[290px] text-[12px] leading-relaxed text-ink-60 rtl:font-arabic">{{ $title }}</p>
                    </div>
                </div>
                <button type="button" @click="pdfOpen = false" class="flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-full border border-ink-10 text-ink-60 transition-colors hover:border-brand hover:bg-brand/5 hover:text-brand dark:border-line" aria-label="{{ __('Close') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" class="size-4" aria-hidden="true"><path d="m18 6-12 12M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="grid grid-cols-2 gap-3 max-[420px]:grid-cols-1">
                <a href="{{ $en }}" @click="pdfOpen = false" class="group flex min-h-[84px] items-center justify-between gap-3 rounded-xl border border-ink-10 bg-white px-4 py-4 transition-[border-color,background,transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:border-brand hover:bg-brand/[0.03] hover:shadow-[0_8px_24px_rgba(200,16,46,0.09)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand dark:border-line dark:bg-surface-1 dark:hover:bg-brand/5" lang="en" dir="ltr">
                    <span class="text-[15px] font-semibold">{{ __('English') }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-[17px] shrink-0 text-ink-30 transition-colors group-hover:text-brand" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5M12 15V3"/></svg>
                </a>
                <a href="{{ $ar }}" @click="pdfOpen = false" class="group flex min-h-[84px] items-center justify-between gap-3 rounded-xl border border-ink-10 bg-white px-4 py-4 transition-[border-color,background,transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:border-brand hover:bg-brand/[0.03] hover:shadow-[0_8px_24px_rgba(200,16,46,0.09)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand dark:border-line dark:bg-surface-1 dark:hover:bg-brand/5" lang="ar" dir="rtl">
                    <span class="text-[15px] font-semibold rtl:font-arabic">{{ __('Arabic') }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-[17px] shrink-0 text-ink-30 transition-colors group-hover:text-brand" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5M12 15V3"/></svg>
                </a>
            </div>
        </div>
    </div>
</div>
