{{--
    One job's page (CareersController@show). The design has no such page: it is built from the site's own pieces — the
    listing header (page-hero), the property page's section titles and dark side card, the careers page's apply window
    and CTA banner.
--}}
@php
    $sectionTitle = 'mb-5 border-l-[3px] border-brand pl-[14px] font-sans text-[11px] leading-[1.2] font-bold tracking-[0.14em] text-ink uppercase rtl:border-r-[3px] rtl:border-l-0 rtl:pr-[14px] rtl:pl-0 rtl:font-arabic';
    $sep = 'mb-10 h-px bg-[rgba(200,16,46,0.2)] dark:bg-line';
    $check = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="mt-[3px] shrink-0 text-brand" aria-hidden="true"><polyline points="20 6 9 17 4 12" /></svg>';
    $facts = array_filter([
        __('Department') => $department,
        __('Job Type') => $type,
        __('Location') => $place,
        __('Salary') => $salary,
        __('Apply Before') => $closes,
    ]);
    $lists = array_filter([__('Responsibilities') => $responsibilities, __('Requirements') => $requirements]);
@endphp

<x-website.layout :title="$metaTitle" :description="$metaDescription">
    <x-website.page-hero
        :crumb="$title"
        :parent="['url' => route('careers'), 'label' => __('Careers')]"
        :title="e($title)"
        :count="$vacancies"
        :count-label="trans_choice('Open Position|Open Positions', $vacancies)"
        :image="asset('images/website/careers-hero.webp')"
        :animate="false"
    />

    <section class="bg-page pt-14 pb-[100px] max-[900px]:pt-8 max-[900px]:pb-[60px] dark:bg-[#100f0d]">
        {{-- The header's width and side spacing, so both line up. --}}
        <div class="mx-auto grid max-w-[1320px] grid-cols-[1fr_340px] items-start gap-10 px-12 max-xl:px-7 max-[900px]:grid-cols-1 max-md:px-5">
            <div>
                @if ($description)
                    <div class="mb-10">
                        <div class="{{ $sectionTitle }}">{{ __('About the Role') }}</div>
                        <div class="text-[16px] leading-[1.8] text-ink-60 rtl:font-arabic [&_li]:mb-1.5 [&_ol]:mb-4 [&_ol]:list-decimal [&_ol]:ps-5 [&_p]:mb-4 [&_strong]:font-semibold [&_strong]:text-ink [&_ul]:mb-4 [&_ul]:list-disc [&_ul]:ps-5">{!! $description !!}</div>
                    </div>
                @endif

                @foreach ($lists as $heading => $items)
                    @if ($description || ! $loop->first)
                        <div class="{{ $sep }}"></div>
                    @endif
                    <div class="mb-10">
                        <div class="{{ $sectionTitle }}">{{ $heading }}</div>
                        <ul class="flex flex-col gap-3">
                            @foreach ($items as $item)
                                <li class="flex items-start gap-3 text-[15px] leading-[1.7] text-ink-60 rtl:font-arabic">{!! $check !!}<span>{{ $item }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>

            <aside x-data class="sticky top-[calc(var(--spacing-nav)+24px)] flex flex-col gap-4 self-start max-[900px]:static max-[900px]:-order-1">
                <div class="overflow-hidden rounded-[14px] bg-[#0d0d0d] p-7 text-white dark:border dark:border-[rgba(212,20,44,0.28)] dark:bg-[linear-gradient(145deg,rgb(26,16,7)_0%,rgb(18,12,6)_100%)]">
                    <p class="mb-5 text-[9px] font-semibold tracking-[0.2em] text-white/45 uppercase rtl:font-arabic rtl:tracking-normal">{{ __('Job Details') }}</p>
                    <div class="mb-6 h-px bg-white/8"></div>
                    <dl class="mb-7 flex flex-col gap-4">
                        @foreach ($facts as $label => $value)
                            <div class="flex items-baseline justify-between gap-4">
                                <dt class="text-[10px] font-semibold tracking-[0.16em] text-white/40 uppercase rtl:font-arabic rtl:tracking-normal">{{ $label }}</dt>
                                <dd class="text-end text-[13px] font-semibold text-white rtl:font-arabic">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    <button
                        type="button"
                        @click="$dispatch('apply-job', @js(['id' => $id, 'title' => $title, 'department' => $department]))"
                        class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-full border border-white/20 bg-[linear-gradient(135deg,rgba(215,20,52,0.96)_0%,rgb(185,10,36)_100%)] px-[26px] py-[14px] font-sans text-[13px] leading-none font-[650] tracking-[0.055em] whitespace-nowrap text-white transition-all duration-300 ease-out-ex hover:-translate-y-0.5 hover:bg-[linear-gradient(135deg,rgba(225,25,58,0.98)_0%,rgb(200,16,46)_100%)] rtl:font-arabic"
                    >
                        {{ __('Apply Now') }}
                        <svg width="14" height="10" viewBox="0 0 16 12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="rtl:-scale-x-100"><path d="M1 6h14M9 1l6 5-6 5" /></svg>
                    </button>
                </div>
                <a href="{{ route('careers') }}" class="inline-flex items-center justify-center gap-2 text-[12px] font-semibold text-ink-60 transition-colors duration-200 hover:text-brand rtl:font-arabic">
                    <svg width="12" height="9" viewBox="0 0 16 12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="ltr:-scale-x-100"><path d="M1 6h14M9 1l6 5-6 5" /></svg>
                    {{ __('All Open Positions') }}
                </a>
            </aside>
        </div>
    </section>

    <livewire:website.job-application />

    <x-website.cta-banner
        :eyebrow="__('Don’t See Your Role?')"
        :title="__('Send Us Your <em>CV Anyway</em>')"
        :description="__('We’re always on the lookout for exceptional talent. Drop us your resume and we’ll reach out when the right opportunity opens.')"
        variant="careers"
    />
</x-website.layout>
