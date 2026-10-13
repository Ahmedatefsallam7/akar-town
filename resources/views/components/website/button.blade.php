{{--
    The design's pill button (.btn-slide): fill (brand gradient) or ghost (white glass). A link when href is given.
    With an arrow, it slides toward the end side on hover (flipped in Arabic).
--}}
@props(['href' => null, 'variant' => 'fill', 'arrow' => false])

@php
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'group inline-flex cursor-pointer items-center gap-2 rounded-full px-[30px] py-[14px] font-sans text-[13px] leading-none font-[650] tracking-[0.055em] whitespace-nowrap transition-all duration-300 ease-out-ex hover:-translate-y-0.5 rtl:font-arabic',
        'border border-white/20 bg-[linear-gradient(135deg,rgba(215,20,52,0.96)_0%,rgb(185,10,36)_100%)] text-white backdrop-blur-[20px] hover:bg-[linear-gradient(135deg,rgba(225,25,58,0.98)_0%,rgb(200,16,46)_100%)]' => $variant === 'fill',
        'border border-white/30 bg-white/10 text-paper backdrop-blur-[24px] backdrop-saturate-180 hover:border-white/50 hover:bg-white/18' => $variant === 'ghost',
    ]);
@endphp

<{{ $href ? 'a' : 'button' }} @if ($href) href="{{ $href }}" @else type="button" @endif {{ $attributes->class($classes) }}>
    {{ $slot }}
    @if ($arrow)
        <x-website.icons.arrow class="transition-transform duration-300 ease-out-ex group-hover:translate-x-1 rtl:-scale-x-100 rtl:group-hover:-translate-x-1" />
    @endif
</{{ $href ? 'a' : 'button' }}>
