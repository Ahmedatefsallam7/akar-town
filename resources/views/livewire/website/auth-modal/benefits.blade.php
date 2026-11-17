{{-- Left: the benefits (not on phones) --}}
<div class="relative flex w-[280px] flex-none flex-col overflow-hidden rounded-s-[18px] bg-[linear-gradient(160deg,#111_0%,#1c1c1c_60%,#0d0d0d_100%)] px-8 pt-11 pb-10 before:pointer-events-none before:absolute before:-top-[60px] before:-left-[60px] before:size-[220px] before:rounded-full before:bg-[radial-gradient(circle,rgba(200,16,46,0.18)_0%,transparent_70%)] after:pointer-events-none after:absolute after:-right-10 after:-bottom-10 after:size-40 after:rounded-full after:bg-[radial-gradient(circle,rgba(200,16,46,0.1)_0%,transparent_70%)] max-[620px]:hidden" aria-hidden="true">
    <div class="relative mx-auto mb-7 size-[120px]">
        <div class="flex size-full items-center justify-center rounded-full border border-white/8 bg-white/5">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" class="size-[54px] text-white opacity-90"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" /><polyline points="9 22 9 12 15 12 15 22" /></svg>
        </div>
        <span class="absolute top-2 right-3 size-[10px] rounded-full bg-brand opacity-90"></span>
        <span class="absolute bottom-4 left-1 size-1.5 rounded-full bg-[rgba(200,16,46,0.6)] opacity-65"></span>
        <span class="absolute top-[30px] left-0 size-1 rounded-full bg-white/40"></span>
    </div>
    <h3 class="mb-4 text-[17px] leading-[1.3] font-bold tracking-[-0.01em] text-white rtl:font-arabic [&_span]:text-brand">{!! __('Keep track of<br><span>your dream home</span>') !!}</h3>
    <ul class="m-0 flex flex-1 list-none flex-col gap-[10px] p-0">
        @foreach ([__('Save and track favourite properties'), __('Be notified of any price change'), __('Unlock exclusive tools & insights')] as $benefit)
            <li class="flex items-start gap-[9px] text-[12.5px] leading-[1.4] text-white/65 rtl:font-arabic"><span class="mt-1 size-1.5 flex-none rounded-full bg-brand"></span>{{ $benefit }}</li>
        @endforeach
    </ul>
</div>
