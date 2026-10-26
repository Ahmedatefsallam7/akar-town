{{--
    The wishlist page (App\Livewire\Website\Wishlist), as the design's wishlist.html: hero with the saved count, then
    Properties and Projects (titled when both have items), an empty state and the "Removed" toast. The browser's
    saved ids are sent once on load; removing a card only updates $store.favourites (with the design's animation).
    @var list<array> $units  PropertyCards::from()
    @var list<array> $projects  ProjectCards::from()
--}}
@php
    $unitKeys = array_column($units, 'id');
    $projectKeys = array_map(fn (array $project) => 'project-'.$project['id'], $projects);
    $saved = fn (int $units, int $projects) => match (true) {
        $units && $projects => __(':items saved', ['items' => trans_choice(':count property|:count properties', $units, ['count' => $units]).__(', ').trans_choice(':count project|:count projects', $projects, ['count' => $projects])]),
        $units > 0 => __(':items saved', ['items' => trans_choice(':count property|:count properties', $units, ['count' => $units])]),
        $projects > 0 => __(':items saved', ['items' => trans_choice(':count project|:count projects', $projects, ['count' => $projects])]),
        default => __('0 items saved'),
    };
    // Every count the page can show while cards are removed, already translated (Arabic plurals stay right).
    $labels = collect(range(0, count($unitKeys)))->map(fn (int $u) => collect(range(0, count($projectKeys)))->map(fn (int $p) => $saved($u, $p))->all())->all();
    $groupTitle = 'font-sans text-[16px] font-semibold tracking-[0.12em] text-ink uppercase rtl:font-arabic rtl:tracking-normal';
    $groupCount = 'flex size-[25px] items-center justify-center rounded-full bg-brand font-sans text-[11px] leading-[1.6] font-medium text-white';
    $grid = $compact ? 'grid grid-cols-2 gap-5 max-[720px]:grid-cols-1' : 'grid grid-cols-3 gap-7 max-[1100px]:grid-cols-2 max-[720px]:grid-cols-1 max-[720px]:gap-5';
@endphp

<div x-data x-init="$wire.load([...$store.favourites.ids])" @class(['bg-page' => ! $compact])>
    <div
        wire:key="wishlist-{{ $loaded ? 'loaded' : 'loading' }}"
        x-data="{
            units: @js($unitKeys),
            projects: @js($projectKeys),
            labels: @js($labels),
            removing: [],
            toast: false,
            timer: null,
            get unitCount() { return this.units.filter((id) => this.$store.favourites.has(id)).length },
            get projectCount() { return this.projects.filter((id) => this.$store.favourites.has(id)).length },
            remove(id) {
                this.removing.push(id)
                setTimeout(() => {
                    this.$store.favourites.toggle(id)
                    this.removing = this.removing.filter((key) => key !== id)
                    clearTimeout(this.timer)
                    this.toast = true
                    this.timer = setTimeout(() => (this.toast = false), 2400)
                }, 350)
            },
        }"
    >
        @unless ($compact)
        <section class="px-12 pt-[calc(var(--spacing-nav)+64px)] max-[720px]:px-6 max-[720px]:pt-[calc(var(--spacing-nav)+40px)] max-[720px]:pb-9">
            <div class="mb-[18px] inline-flex items-center gap-[10px] text-[10px] font-bold tracking-[0.22em] text-brand uppercase before:inline-block before:h-px before:w-7 before:bg-brand before:opacity-60 rtl:font-arabic rtl:tracking-normal">{{ __('Wishlist') }}</div>
            <div class="flex flex-wrap items-end justify-between gap-6">
                <h1 class="text-[clamp(36px,5vw,64px)] leading-[1.05] font-light tracking-[-0.025em] text-ink rtl:font-arabic rtl:leading-[1.3]">{{ __('Your Favourites') }}</h1>
                @if ($loaded)
                    <div class="inline-flex shrink-0 items-center gap-2 rounded-full border border-ink-10 px-[18px] py-2 text-[13px] font-medium whitespace-nowrap text-ink-60 rtl:font-arabic">
                        <span class="size-1.5 shrink-0 rounded-full bg-brand"></span>
                        <span x-text="labels[unitCount][projectCount]">{{ $saved(count($unitKeys), count($projectKeys)) }}</span>
                    </div>
                @endif
            </div>
        </section>
        @endunless

        <section @class(['p-12 max-[720px]:px-6 max-[720px]:pt-9 max-[720px]:pb-[60px]' => ! $compact, 'py-2' => $compact])>
            <div class="flex flex-col gap-14 max-[720px]:gap-10">
                @if ($units)
                    <div x-show="unitCount">
                        <div x-show="unitCount && projectCount" class="mb-6 flex items-center gap-3 pb-4">
                            <h2 class="{{ $groupTitle }}">{{ __('Properties') }}</h2>
                            <span class="{{ $groupCount }}" x-text="unitCount">{{ count($units) }}</span>
                        </div>
                        <div class="{{ $grid }}">
                            @foreach ($units as $unit)
                                <x-website.wishlist-card :item="$unit" fav="{{ $unit['id'] }}" />
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($projects)
                    <div x-show="projectCount">
                        <div x-show="unitCount && projectCount" class="mb-6 flex items-center gap-3 pb-4">
                            <h2 class="{{ $groupTitle }}">{{ __('Projects') }}</h2>
                            <span class="{{ $groupCount }}" x-text="projectCount">{{ count($projects) }}</span>
                        </div>
                        <div class="{{ $grid }}">
                            @foreach ($projects as $project)
                                <x-website.wishlist-card :item="$project" fav="'project-{{ $project['id'] }}'" project />
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            @if ($loaded)
                <div x-show="! unitCount && ! projectCount" @if ($units || $projects) style="display: none" @endif @class(['flex flex-col items-center px-8 pt-[100px] pb-[120px] text-center' => ! $compact, 'flex flex-col items-center px-5 py-10 text-center' => $compact])>
                    <div class="mb-7 flex size-[72px] animate-heart-pulse items-center justify-center rounded-full border-2 border-ink-30">
                        <svg viewBox="0 0 24 24" aria-hidden="true" class="size-[30px] fill-none stroke-ink-30 stroke-[1.5]"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" /></svg>
                    </div>
                    <h2 class="mb-3 text-[28px] font-light tracking-[-0.02em] text-ink rtl:font-arabic">{{ __('Your wishlist is empty') }}</h2>
                    <p class="mb-9 max-w-[380px] text-[14.5px] leading-[1.6] text-ink-60 rtl:font-arabic">{{ __("Start saving properties you love and find them all here, ready for when you're ready.") }}</p>
                    <a href="{{ route('properties') }}" class="inline-flex items-center gap-2 rounded-full bg-brand px-8 py-[14px] text-[13px] leading-[normal] font-[650] tracking-[0.055em] text-white transition-[background,translate] duration-250 hover:-translate-y-0.5 hover:bg-brand-lt rtl:font-arabic rtl:tracking-normal">
                        {{ __('Explore Properties') }}
                        <svg viewBox="0 0 16 14" aria-hidden="true" class="size-[13px] flex-none fill-none stroke-current stroke-2 rtl:-scale-x-100"><path d="M8 0.414C8 1.11 8.69 2.152 9.387 3.027C10.286 4.154 11.357 5.138 12.587 5.889C13.509 6.452 14.628 6.992 15.526 6.992M8 13.586C8 12.889 8.69 11.848 9.387 10.973C10.286 9.846 11.357 8.862 12.587 8.111C13.509 7.548 14.628 7.008 15.526 7.008M15.526 6.999H0.474" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    </a>
                </div>
            @endif
        </section>

        <div
            class="pointer-events-none fixed bottom-8 left-1/2 z-999 -translate-x-1/2 rounded-full bg-ink px-[22px] py-3 text-[13px] font-medium whitespace-nowrap text-paper opacity-0 transition-[opacity,translate] duration-250 ease-out-ex rtl:font-arabic"
            :class="toast ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
            role="status"
            aria-live="polite"
            x-text="toast ? @js(__('Removed from wishlist')) : ''"
        ></div>
    </div>
</div>
