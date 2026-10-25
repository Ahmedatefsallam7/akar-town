{{--
    The design's full-screen centred hero of the contact, careers and about pages (.contact-hero / .career-hero /
    .about-hero): photo with parallax, grain and shade, eyebrow, two diamond rules around the title (may hold <em>, shown
    on its own line when emBlock) and a short text. Phones take the 900 px copy of the photo. At least the screen's
    height (the design's 100vh), taller when an admin text is long so nothing is cut; on phones it follows the content.
--}}
@props(['eyebrow', 'title', 'description', 'image', 'imageSmall', 'label', 'brightness' => 'brightness-50 grayscale-25', 'emBlock' => false])

@php
    $rule = 'mb-8 flex w-full max-w-[700px] items-center gap-[18px] before:h-px before:flex-1 before:bg-[linear-gradient(to_right,transparent,rgba(255,255,255,0.18),transparent)] after:h-px after:flex-1 after:bg-[linear-gradient(to_right,transparent,rgba(255,255,255,0.18),transparent)]';
    $diamond = '<div class="size-[7px] shrink-0 rotate-45 border border-[rgba(200,16,46,0.7)] bg-[rgba(200,16,46,0.15)]"></div>';
@endphp

@push('head')
    <link rel="preload" as="image" href="{{ $imageSmall }}" media="(max-width: 768px)" fetchpriority="high" />
    <link rel="preload" as="image" href="{{ $image }}" media="(min-width: 769px)" fetchpriority="high" />
@endpush

<section
    x-data
    x-init="const bg = $refs.bg; const move = () => window.scrollY < window.innerHeight && (bg.style.transform = `translateY(${window.scrollY * 0.35}px)`); window.addEventListener('scroll', move, { passive: true }); move()"
    class="relative flex min-h-[max(100vh,680px)] flex-col overflow-hidden bg-[#07060a] max-md:min-h-0"
    aria-label="{{ $label }}"
>
    <div x-ref="bg" class="absolute inset-0 bg-[#07060a] bg-(image:--bg-small) bg-cover bg-center bg-no-repeat {{ $brightness }} transition-transform duration-50 ease-linear will-change-transform min-[769px]:bg-(image:--bg)" style="--bg: url('{{ $image }}'); --bg-small: url('{{ $imageSmall }}')" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-0 z-1 bg-(image:--grain) bg-size-[200px_auto] opacity-4" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-0 z-2 bg-[radial-gradient(ellipse_75%_75%_at_50%_45%,rgba(7,6,10,0.35)_0%,rgba(7,6,10,0.72)_100%),linear-gradient(to_bottom,rgba(7,6,10,0.65)_0%,rgba(7,6,10,0.22)_40%,rgba(7,6,10,0.68)_100%)]" aria-hidden="true"></div>

    <div x-intersect.once.threshold.12="$el.classList.add('is-in')" class="relative z-5 flex flex-1 flex-col items-center justify-center px-12 pt-nav pb-5 text-center opacity-0 [transform:translateY(32px)] transition-[opacity,transform] duration-900 ease-out-ex max-[1100px]:px-6 max-md:px-5 max-md:pt-[calc(var(--spacing-nav)+48px)] max-md:pb-16 [&.is-in]:opacity-100 [&.is-in]:[transform:none]">
        <span class="mb-7 text-[9px] font-bold tracking-[0.35em] text-brand uppercase rtl:font-arabic rtl:tracking-normal">{{ $eyebrow }}</span>
        <div class="{{ $rule }}" aria-hidden="true">{!! $diamond !!}</div>
        <h1 @class([
            'mb-8 font-sans text-[clamp(62px,8.5vw,128px)] leading-[0.96] font-light tracking-[-0.028em] text-white rtl:font-arabic rtl:leading-[1.25] rtl:tracking-normal max-md:text-[clamp(46px,10vw,72px)] [&_em]:text-white/58 [&_em]:not-italic',
            '[&_em]:block' => $emBlock,
        ])>{!! $title !!}</h1>
        <div class="{{ $rule }}" aria-hidden="true">{!! $diamond !!}</div>
        <p class="max-w-[50ch] text-[clamp(14px,1.1vw,16px)] leading-[1.85] font-light text-white/48 rtl:font-arabic">{{ $description }}</p>
    </div>
</section>
