{{-- The design's "View All" glass pill (.btn-view-all): brand red on hover, arrow slides on hover. --}}
@props(['href'])

<a href="{{ $href }}" {{ $attributes->class('group inline-flex items-center gap-2 rounded-full border-[1.5px] border-white/65 bg-white/48 px-8 py-[14px] font-sans text-[13px] leading-none font-[650] tracking-[0.055em] whitespace-nowrap text-brand-dk shadow-[0_2px_16px_rgba(0,0,0,0.08),0_1px_4px_rgba(0,0,0,0.05)] backdrop-blur-[20px] backdrop-saturate-200 transition-all duration-300 ease-out-ex hover:-translate-y-0.5 hover:border-[rgba(200,16,46,0.7)] hover:bg-[linear-gradient(135deg,rgba(215,20,52,0.96)_0%,rgb(185,10,36)_100%)] hover:text-white hover:shadow-[0_6px_24px_rgba(0,0,0,0.12),0_2px_6px_rgba(0,0,0,0.07)] dark:border-white/28 dark:bg-transparent dark:text-white dark:shadow-none dark:backdrop-blur-none dark:hover:border-white/48 dark:hover:bg-white/8 dark:hover:shadow-[0_4px_20px_rgba(0,0,0,0.3)] rtl:font-arabic') }}>
    {{ $slot }}
    <x-website.icons.arrow class="size-[14px] transition-transform duration-300 ease-out-ex group-hover:translate-x-1 rtl:-scale-x-100 rtl:group-hover:-translate-x-1" />
</a>
