{{-- Properties listing (the design's listings.html): page header, then the Livewire search. --}}
<x-website.layout :title="__('Properties')">
    <div class="relative flex min-h-[360px] flex-col justify-end overflow-hidden bg-[rgb(6,6,10)] bg-cover bg-no-repeat bg-[position:center_38%] pt-nav before:pointer-events-none before:absolute before:inset-0 before:z-0 before:bg-[linear-gradient(108deg,rgba(6,6,10,0.96)_0%,rgba(6,6,10,0.75)_32%,rgba(6,6,10,0.28)_58%,transparent_100%),linear-gradient(to_top,rgba(6,6,10,0.88)_0%,rgba(6,6,10,0.2)_45%,transparent_70%)]" style="background-image: url('{{ asset('images/website/why-bg.webp') }}')">
        <div class="relative z-1 mx-auto flex w-full max-w-[1320px] items-end justify-between gap-10 px-12 pt-14 pb-[52px] max-xl:px-7 max-md:flex-col max-md:gap-4 max-md:px-5 max-md:pt-10 max-md:pb-9">
            <div>
                <nav class="mb-4 flex items-center gap-2 text-[10px] tracking-[0.2em] text-white/55 uppercase rtl:font-arabic" aria-label="{{ __('Breadcrumb') }}">
                    <a href="{{ route('home') }}" class="text-white/55 transition-colors duration-200 hover:text-brand">{{ __('Home') }}</a>
                    <span class="text-white/28" aria-hidden="true">›</span>
                    <span class="text-white/28">{{ __('Properties') }}</span>
                </nav>
                <h1 class="font-sans text-[clamp(38px,5.2vw,68px)] leading-[1.08] font-light tracking-[-0.02em] text-white rtl:font-arabic max-[480px]:text-[clamp(28px,8vw,48px)] [&_em]:font-light [&_em]:text-brand-lt [&_em]:not-italic">{!! __('Discover <em>Exceptional</em> Properties') !!}</h1>
            </div>
            <div class="shrink-0 text-right max-md:text-left rtl:max-md:text-right">
                <div
                    x-data="{ shown: 0 }"
                    x-init="const step = () => { shown = Math.min(shown + 5, {{ $available }}); if (shown < {{ $available }}) requestAnimationFrame(step) }; requestAnimationFrame(step)"
                    x-text="shown"
                    class="font-sans text-[60px] leading-none font-light tracking-[-0.02em] text-white max-[700px]:text-[40px] max-[480px]:text-[32px]"
                >{{ $available }}</div>
                <div class="mt-1.5 text-[10px] font-semibold tracking-[0.24em] text-brand uppercase rtl:font-arabic">{{ __('Available Properties') }}</div>
            </div>
        </div>
    </div>

    <livewire:website.properties />
</x-website.layout>
