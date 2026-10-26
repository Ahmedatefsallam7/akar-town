{{--
    Careers page (the design's career.html): hero, the open jobs (Settings: Careers, active and not past the closing
    date) with a department filter that works in the browser, the "Apply Now" window and the CTA banner.
--}}
@php
    $data = \App\Support\Website\CareerCards::open();
    $jobs = $data['jobs'];
    $reveal = 'opacity-0 [transform:translateY(32px)] transition-[opacity,transform] duration-900 ease-out-ex [&.is-in]:opacity-100 [&.is-in]:[transform:none]';
    $pill = 'cursor-pointer rounded-full border px-4 py-[7px] text-[11px] font-semibold tracking-[0.06em] transition-all duration-200 rtl:font-arabic rtl:tracking-normal';
@endphp

<x-website.layout :title="__('Careers')">
    <x-website.statement-hero
        :eyebrow="__('Shape the Future of Real Estate')"
        :title="__('Build Something<br><em>Remarkable</em>')"
        :description="__('Join a team of passionate professionals redefining luxury real estate across Egypt’s most prestigious destinations.')"
        :image="\App\Support\Website\SiteImages::get('careers_hero')[0]"
        :image-small="\App\Support\Website\SiteImages::get('careers_hero')[1]"
        :label="__('Careers at Akar Town')"
        brightness="brightness-48 grayscale-20"
    />

    <section
        id="careers"
        x-data="{
            filter: 'all',
            departments: @js(array_column($jobs, 'department')),
            {{-- "N Roles" for every count the filter can give (Arabic has several plural forms). --}}
            roles: @js(array_map(fn (int $n) => trans_choice(':count Role|:count Roles', $n, ['count' => $n]), range(0, count($jobs)))),
            get count() {
                return this.filter === 'all' ? this.departments.length : this.departments.filter((department) => department === this.filter).length
            },
        }"
        class="relative overflow-hidden bg-page py-[clamp(80px,10vw,130px)] before:absolute before:inset-x-12 before:top-0 before:h-px before:bg-[linear-gradient(to_right,var(--color-brand)_0%,rgba(200,16,46,0.18)_45%,transparent_100%)] max-[1100px]:before:inset-x-6 rtl:before:bg-[linear-gradient(to_left,var(--color-brand)_0%,rgba(200,16,46,0.18)_45%,transparent_100%)] dark:bg-[#100f0d]"
        aria-label="{{ __('Open Positions') }}"
    >
        <div x-intersect.once.threshold.12="$el.classList.add('is-in')" class="{{ $reveal }} mx-auto max-w-[1320px] px-12 max-[1100px]:px-6 max-md:px-5">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <span class="text-[11px] font-bold tracking-[0.22em] text-ink-30 uppercase rtl:font-arabic rtl:tracking-normal dark:text-white/30">{{ __('Open Positions') }}</span>
                <span class="rounded-full bg-[rgba(200,16,46,0.08)] px-3 py-1 text-[11px] font-semibold text-brand rtl:font-arabic" x-text="roles[count]">{{ trans_choice(':count Role|:count Roles', count($jobs), ['count' => count($jobs)]) }}</span>
            </div>

            @if ($jobs)
                @if ($data['departments'])
                    <div class="mb-7 flex flex-wrap gap-2" role="group" aria-label="{{ __('Filter by department') }}">
                        @foreach (['all' => __('All')] + $data['departments'] as $slug => $name)
                            <button
                                type="button"
                                @click="filter = @js($slug)"
                                :aria-pressed="filter === @js($slug)"
                                class="{{ $pill }}"
                                :class="filter === @js($slug) ? 'border-brand bg-brand text-white' : 'border-ink-10 bg-white text-ink-60 hover:border-[rgba(200,16,46,0.3)] hover:text-ink dark:border-white/10 dark:bg-[#1a1916] dark:text-white/50 dark:hover:border-[rgba(200,16,46,0.4)] dark:hover:text-white'"
                            >{{ $name }}</button>
                        @endforeach
                    </div>
                @endif

                <div class="flex flex-col gap-[14px]">
                    @foreach ($jobs as $job)
                        <div
                            x-show="filter === 'all' || filter === @js($job['department'])"
                            class="group/job relative flex items-center gap-6 rounded-2xl border border-ink-10 bg-white px-7 py-[26px] [transition:border-color_0.28s,box-shadow_0.28s] hover:border-[rgba(200,16,46,0.28)] max-md:flex-col max-md:items-start dark:border-white/8 dark:bg-[#1a1916] dark:hover:border-[rgba(200,16,46,0.3)] dark:hover:shadow-[0_8px_36px_rgba(0,0,0,0.4)]"
                        >
                            <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-[rgba(200,16,46,0.08)] text-brand transition-[background] duration-250 group-hover/job:bg-[rgba(200,16,46,0.15)]" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $job['icon'] !!}</svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                {{-- The whole card opens the job's page; "Apply Now" sits above that link. --}}
                                <h3 class="mb-1.5 text-[16px] font-semibold tracking-[-0.01em] text-ink transition-colors duration-200 group-hover/job:text-brand rtl:font-arabic rtl:tracking-normal"><a href="{{ route('careers.show', $job['slug']) }}" class="after:absolute after:inset-0 after:rounded-2xl">{{ $job['title'] }}</a></h3>
                                <div class="flex flex-wrap items-center gap-[10px]">
                                    @if ($job['departmentName'])
                                        <span class="rounded-full bg-[rgba(200,16,46,0.08)] px-[10px] py-[3px] text-[10px] font-bold tracking-[0.1em] text-brand uppercase rtl:font-arabic rtl:tracking-normal">{{ $job['departmentName'] }}</span>
                                    @endif
                                    <span class="rounded-full bg-ink-10 px-[10px] py-[3px] text-[10px] font-bold tracking-[0.1em] text-ink-60 uppercase rtl:font-arabic rtl:tracking-normal dark:bg-white/8 dark:text-white/50">{{ $job['type'] }}</span>
                                    @if ($job['place'])
                                        <span class="flex items-center gap-1 text-[12px] text-ink-30 rtl:font-arabic dark:text-white/30">
                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" /><circle cx="12" cy="10" r="3" /></svg>
                                            {{ $job['place'] }}
                                        </span>
                                    @endif
                                </div>
                                @if ($job['summary'])
                                    <p class="mt-[10px] line-clamp-2 text-[13px] leading-[1.6] text-ink-60 rtl:font-arabic dark:text-white/50">{{ $job['summary'] }}</p>
                                @endif
                            </div>
                            <div class="relative z-1 shrink-0 max-md:self-start">
                                <button
                                    type="button"
                                    @click="$dispatch('apply-job', @js(['id' => $job['id'], 'title' => $job['title'], 'department' => $job['departmentName']]))"
                                    class="inline-flex cursor-pointer items-center gap-[7px] rounded-full bg-[linear-gradient(135deg,rgba(215,20,52,0.96)_0%,rgb(185,10,36)_100%)] px-5 py-[10px] text-[11px] font-bold tracking-[0.08em] whitespace-nowrap text-white transition-all duration-250 ease-out-ex hover:-translate-y-px rtl:font-arabic rtl:tracking-normal"
                                >
                                    {{ __('Apply Now') }}
                                    <svg width="12" height="9" viewBox="0 0 16 12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="rtl:-scale-x-100"><path d="M1 6h14M9 1l6 5-6 5" /></svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="px-6 py-12 text-center text-[14px] text-ink-30 rtl:font-arabic">{{ __('No open positions right now — send us your CV anyway.') }}</p>
            @endif
        </div>
    </section>

    @if ($jobs)
        <livewire:website.job-application />
    @endif

    <x-website.cta-banner
        :eyebrow="__('Don’t See Your Role?')"
        :title="__('Send Us Your <em>CV Anyway</em>')"
        :description="__('We’re always on the lookout for exceptional talent. Drop us your resume and we’ll reach out when the right opportunity opens.')"
        variant="careers"
    />
</x-website.layout>
