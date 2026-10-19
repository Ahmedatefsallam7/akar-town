{{--
    Compare (the design's compare.html): hero with the number of units, the photo accordion (remove / add more), a
    strip that sticks under the navbar once the photos are passed, the comparison table (overview, specifications,
    amenities, with the best cell of each figure and animated bars) and "Ready to Decide?" cards. Rows nobody has
    data for aren't shown. Without units: the empty state.
    @var list<array> $units  CompareUnits::for()['units']
    @var array<string, list<bool>> $best
    @var list<string> $amenities
--}}
@php
    $business = app(\Modules\GeneralSettings\Settings\BusinessInfoSettings::class);
    $count = count($units);
    $has = fn (string $key) => collect($units)->contains(fn (array $unit) => filled($unit[$key]));
    $statusStyles = [
        'green' => 'border-[rgba(30,126,82,0.22)] bg-[rgba(30,126,82,0.1)] text-[#1e7e52]',
        'red' => 'border-[rgba(200,16,46,0.28)] bg-[rgba(200,16,46,0.1)] text-brand',
        'ink' => 'border-ink-10 bg-ink-10 text-ink',
    ];
    $sectionLabel = 'sticky left-0 z-10 flex flex-[0_0_var(--lw)] items-end bg-page pt-8 pr-5 pb-[14px] rtl:right-0 rtl:left-auto rtl:pr-0 rtl:pl-5';
    $sectionText = 'inline-flex items-center gap-[10px] font-sans text-[15px] tracking-[0.02em] text-brand after:block after:h-px after:w-7 after:bg-[linear-gradient(to_right,var(--color-brand-lt),transparent)] rtl:font-arabic rtl:after:bg-[linear-gradient(to_left,var(--color-brand-lt),transparent)]';
    $sectionCell = 'min-w-[230px] flex-1 border-l border-ink-10 px-6 pt-8 pb-[14px] rtl:border-r rtl:border-l-0 max-[600px]:min-w-[180px]';
    $label = 'sticky left-0 z-10 flex flex-[0_0_var(--lw)] items-center bg-page py-5 pr-5 text-[11px] font-semibold tracking-[0.12em] text-ink-60 uppercase transition-[background] duration-150 group-hover:bg-[rgba(250,248,245,0.99)] dark:group-hover:bg-surface-0 rtl:right-0 rtl:left-auto rtl:pr-0 rtl:pl-5 rtl:font-arabic rtl:tracking-normal';
    $cell = 'relative flex min-w-[230px] flex-1 flex-col justify-center border-l border-[rgba(13,13,13,0.04)] px-6 py-5 transition-[background] duration-150 group-hover:bg-[rgba(200,16,46,0.016)] dark:border-line rtl:border-r rtl:border-l-0 max-[600px]:min-w-[180px] max-[600px]:px-[14px] max-[600px]:py-4';
    $bestCell = 'bg-[rgba(200,16,46,0.055)]! after:absolute after:inset-x-0 after:top-0 after:h-0.5 after:bg-[linear-gradient(to_right,var(--color-brand),var(--color-brand-lt))] rtl:after:bg-[linear-gradient(to_left,var(--color-brand),var(--color-brand-lt))]';
    $value = 'flex flex-wrap items-baseline gap-[5px] font-sans text-[30px] leading-none text-ink max-[600px]:text-[22px]';
    $unitText = 'font-sans text-[13px] text-ink-30';
    $badge = 'mt-1.5 inline-flex items-center gap-1 self-start rounded-full border-[1.5px] border-[rgba(200,16,46,0.35)] bg-white/12 px-[10px] py-[3px] text-[9px] font-bold tracking-[0.12em] text-brand uppercase backdrop-blur-[8px] rtl:font-arabic rtl:tracking-normal';
    $text = 'text-[15px] text-ink rtl:font-arabic max-[600px]:text-[14px]';
    $bar = 'mt-2 h-[3px] w-full overflow-hidden rounded-[3px] bg-ink-10';
    $barFill = 'h-full w-0 rounded-[3px] bg-[linear-gradient(to_right,var(--color-brand-dk),var(--color-brand))] transition-[width] duration-1100 ease-out-ex rtl:bg-[linear-gradient(to_left,var(--color-brand-dk),var(--color-brand))]';
    $pin = '<svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true" class="mt-[3px] shrink-0 text-brand"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z" /><circle cx="12" cy="10" r="3" /></svg>';
    $rows = [
        __('Overview') => array_filter([
            'price' => $has('price') ? __('Price') : null,
            'status' => $has('status') ? __('Status') : null,
            'type' => $has('type') ? __('Type') : null,
            'location' => $has('location') ? __('Location') : null,
            'date' => $has('date') ? __('Listed') : null,
            'agent' => $has('agent') ? __('Agent') : null,
        ]),
        __('Specifications') => array_filter([
            'beds' => $has('beds') ? __('Bedrooms') : null,
            'baths' => $has('baths') ? __('Bathrooms') : null,
            'area' => $has('area') ? __('Area') : null,
            'floor' => $units && collect($units)->contains(fn (array $unit) => $unit['floor'] !== null) ? __('Floor') : null,
        ]),
        __('Amenities') => array_combine($amenities, $amenities),
    ];
@endphp

<x-website.layout :title="__('Compare')">
    <div
        x-data="{
            units: @js(array_column($units, 'slug', 'id')),
            strip: false,
            remove(id) {
                this.$store.compare.remove(id)
                const rest = Object.entries(this.units).filter(([key]) => Number(key) !== id).map(([, slug]) => slug)
                window.location.href = rest.length ? @js(route('compare')) + '?units=' + rest.join(',') : @js(route('properties'))
            },
        }"
        class="bg-page leading-[normal] [--lw:196px] max-[900px]:[--lw:130px] max-[600px]:[--lw:100px]"
    >
        {{-- Hero --}}
        <div class="relative flex min-h-[360px] flex-col justify-end overflow-hidden bg-[rgb(6,6,10)] bg-cover bg-[center_38%] bg-no-repeat pt-nav before:pointer-events-none before:absolute before:inset-0 before:z-0 before:bg-[linear-gradient(108deg,rgba(6,6,10,0.96)_0%,rgba(6,6,10,0.75)_32%,rgba(6,6,10,0.28)_58%,transparent_100%),linear-gradient(to_top,rgba(6,6,10,0.88)_0%,rgba(6,6,10,0.2)_45%,transparent_70%)]" style="background-image: url('{{ asset('images/website/why-bg.webp') }}')">
            <div class="relative z-1 flex max-w-[1320px] items-end justify-between gap-10 px-12 pt-14 pb-[52px] max-[900px]:flex-col max-[900px]:items-start max-[900px]:gap-4 max-[900px]:px-6 max-[900px]:pb-9 max-[600px]:items-center! max-[600px]:gap-5! max-[600px]:px-6! max-[600px]:pt-10! max-[600px]:pb-11! max-[600px]:text-center">
                <div class="max-[600px]:flex max-[600px]:flex-col max-[600px]:items-center">
                    <nav class="mb-[14px] flex items-center gap-2 text-[11px] tracking-[0.16em] text-white/40 uppercase rtl:font-arabic rtl:tracking-[0.02em] rtl:normal-case max-[600px]:mb-4 max-[600px]:justify-center" aria-label="{{ __('Breadcrumb') }}">
                        <a href="{{ route('home') }}" class="text-white/40 transition-colors duration-200 hover:text-brand">{{ __('Home') }}</a>
                        <span class="text-white/20 rtl:-scale-x-100" aria-hidden="true">›</span>
                        <a href="{{ route('properties') }}" class="text-white/40 transition-colors duration-200 hover:text-brand">{{ __('Properties') }}</a>
                        <span class="text-white/20 rtl:-scale-x-100" aria-hidden="true">›</span>
                        <span class="text-white/20">{{ __('Compare') }}</span>
                    </nav>
                    <h1 class="font-sans text-[clamp(34px,5vw,60px)] leading-[1.05] font-light tracking-[-0.01em] text-white rtl:font-arabic rtl:leading-[1.3] max-[600px]:text-[clamp(26px,7vw,36px)] [&_em]:text-[1.02em] [&_em]:text-brand [&_em]:not-italic">{!! __('Compare <em>Properties</em>') !!}</h1>
                </div>
                <div class="shrink-0 text-right rtl:text-left max-[900px]:text-left max-[900px]:rtl:text-right max-[600px]:inline-flex! max-[600px]:flex-row! max-[600px]:items-center! max-[600px]:gap-[10px]! max-[600px]:rounded-full max-[600px]:border max-[600px]:border-[rgba(200,16,46,0.22)] max-[600px]:bg-[rgba(200,16,46,0.1)] max-[600px]:py-[10px] max-[600px]:pr-5 max-[600px]:pl-[14px] max-[600px]:text-left! max-[600px]:backdrop-blur-[12px]">
                    <div class="font-sans text-[52px] leading-none font-light text-brand max-[900px]:text-[38px] max-[600px]:min-w-8 max-[600px]:text-center max-[600px]:text-[32px]!">{{ $count }}</div>
                    <div class="mt-1 text-[11px] tracking-[0.18em] text-white/40 uppercase rtl:font-arabic rtl:tracking-normal max-[600px]:mt-0! max-[600px]:max-w-20 max-[600px]:text-[10px] max-[600px]:leading-[1.4] max-[600px]:text-white/55">{{ __('Units Selected') }}</div>
                </div>
            </div>
        </div>

        @if ($units)
            {{-- Photo accordion --}}
            <div x-intersect:enter="strip = false" x-intersect:leave="strip = true" class="border-t border-white/6 bg-[rgb(17,17,17)]">
                <div class="mx-auto flex max-w-[1320px] overflow-hidden px-12 max-[900px]:px-6">
                    @foreach ($units as $i => $unit)
                        <div class="group relative h-[260px] min-w-0 flex-1 overflow-hidden border-r border-white/4 transition-[flex] duration-500 ease-out-ex last:border-r-0 hover:flex-[1.55] rtl:border-r-0 rtl:border-l rtl:last:border-l-0 max-[900px]:h-[200px] max-[600px]:h-[170px]">
                            <a href="{{ $unit['url'] }}" class="absolute inset-0" aria-label="{{ $unit['title'] }}">
                                <img src="{{ $unit['image'] }}" alt="{{ $unit['title'] }}" loading="lazy" class="absolute inset-0 size-full object-cover brightness-70 saturate-90 transition-[transform,filter] duration-700 ease-smooth group-hover:scale-106 group-hover:brightness-80 group-hover:saturate-110" />
                                <div class="absolute inset-0 bg-[linear-gradient(to_top,rgba(0,0,0,0.9)_0%,rgba(0,0,0,0.35)_55%,rgba(0,0,0,0.05)_100%)]"></div>
                            </a>
                            <div class="pointer-events-none absolute top-4 left-4 z-4 flex size-6 items-center justify-center rounded-full bg-brand text-[10px] font-bold text-white rtl:right-4 rtl:left-auto">{{ $i + 1 }}</div>
                            <button type="button" @click="remove({{ $unit['id'] }})" class="absolute top-[14px] right-[14px] z-4 flex size-[26px] cursor-pointer items-center justify-center rounded-full border border-white/12 bg-black/45 text-white/60 backdrop-blur-[8px] transition-all duration-200 hover:border-danger hover:bg-[rgba(204,34,34,0.65)] hover:text-white rtl:right-auto rtl:left-[14px]" aria-label="{{ __('Remove :property', ['property' => $unit['title']]) }}">
                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
                            </button>
                            <div class="pointer-events-none absolute inset-x-0 bottom-0 z-3 px-[18px] pt-4 pb-5">
                                @if ($unit['type'])
                                    <div class="mb-[5px] text-[9px] font-bold tracking-[0.2em] text-brand uppercase rtl:font-arabic rtl:tracking-normal">{{ $unit['type'] }}</div>
                                @endif
                                <div class="mb-1.5 truncate font-sans text-[clamp(14px,1.8vw,20px)] leading-[1.2] font-light text-white group-hover:whitespace-normal rtl:font-arabic">{{ $unit['title'] }}</div>
                                @if ($unit['price'])
                                    <div class="font-sans text-[clamp(16px,2.2vw,24px)] leading-none font-semibold text-brand">{{ __('EGP :price', ['price' => $unit['price']]) }}</div>
                                    <div class="mt-0.5 text-[8px] tracking-[0.12em] text-white/45 uppercase rtl:font-arabic rtl:tracking-normal">{{ __('Total Price') }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    @if ($count < \App\Support\Website\CompareUnits::MAX)
                        <a href="{{ route('properties') }}" class="flex flex-[0_0_110px] cursor-pointer flex-col items-center justify-center gap-[10px] border-l border-white/6 bg-white/3 p-5 text-white/35 transition-all duration-300 hover:bg-[rgba(200,16,46,0.07)] hover:text-brand rtl:border-r rtl:border-l-0 max-[600px]:flex-[0_0_80px]" title="{{ __('Add more properties') }}">
                            <div class="flex size-[38px] items-center justify-center rounded-full border-[1.5px]">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
                            </div>
                            <span class="text-center text-[9px] tracking-[0.14em] uppercase rtl:font-arabic rtl:tracking-normal">{{ __('Add More') }}</span>
                        </a>
                    @endif
                </div>
            </div>

            {{-- Strip under the navbar once the photos are passed --}}
            <div class="fixed inset-x-0 top-nav z-150 overflow-hidden border-b border-white/6 bg-[rgba(10,10,10,0.95)] -translate-y-full backdrop-blur-[24px] transition-transform duration-400 ease-out-ex" :class="strip && 'translate-y-0!'" aria-hidden="true">
                <div class="mx-auto flex max-w-[1320px] items-stretch px-12 max-[900px]:px-6">
                    <div class="flex flex-[0_0_var(--lw)] items-center border-r border-white/6 pr-4 text-[14px] font-bold tracking-[0.14em] text-white/55 uppercase rtl:border-r-0 rtl:border-l rtl:pr-0 rtl:pl-4 rtl:font-arabic rtl:tracking-normal">{{ __('Comparing') }}</div>
                    @foreach ($units as $unit)
                        <div class="flex min-w-0 flex-1 items-center gap-[10px] border-r border-white/5 px-4 py-2 last:border-r-0 rtl:border-r-0 rtl:border-l rtl:last:border-l-0">
                            <img src="{{ $unit['image'] }}" alt="" loading="lazy" class="size-[34px] shrink-0 rounded-[5px] object-cover" />
                            <div class="min-w-0">
                                <div class="truncate text-[18px] font-medium text-white rtl:font-arabic max-[900px]:max-w-[100px] max-[900px]:text-[13px]">{{ $unit['title'] }}</div>
                                @if ($unit['price'])
                                    <div class="text-[14px] text-brand max-[900px]:text-[12px]">{{ __('EGP :price', ['price' => $unit['price']]) }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Comparison table --}}
            <main class="pt-12">
                <div class="mx-auto max-w-[1320px] px-12 max-[900px]:px-6">
                    <div class="overflow-x-auto pb-0.5 [&::-webkit-scrollbar]:h-[3px] [&::-webkit-scrollbar-thumb]:rounded-[2px] [&::-webkit-scrollbar-thumb]:bg-brand [&::-webkit-scrollbar-track]:bg-ink-10">
                        <div class="block min-w-max">
                            @foreach ($rows as $section => $sectionRows)
                                @continue(! $sectionRows)
                                <div class="mt-2 flex items-stretch border-b border-ink-10">
                                    <div class="{{ $sectionLabel }}"><span class="{{ $sectionText }}">{{ $section }}</span></div>
                                    <div class="flex flex-1">
                                        @foreach ($units as $unit)
                                            <div class="{{ $sectionCell }}"></div>
                                        @endforeach
                                    </div>
                                </div>

                                @foreach ($sectionRows as $key => $rowLabel)
                                    <div class="group flex items-stretch border-b border-[rgba(13,13,13,0.043)] transition-[background] duration-150 hover:bg-[rgba(200,16,46,0.02)] dark:border-line">
                                        <div class="{{ $label }}">{{ $rowLabel }}</div>
                                        <div class="flex flex-1">
                                            @foreach ($units as $i => $unit)
                                                @php
                                                    $isBest = $section !== __('Amenities') && ($best[$key][$i] ?? false);
                                                @endphp
                                                {{-- As the design: the cheapest price gets the badge only, other bests also the highlighted cell. --}}
                                                <div @class([$cell, $bestCell => $isBest && $key !== 'price'])>
                                                    @if ($section === __('Amenities'))
                                                        @if (in_array($key, $unit['amenities'], true))
                                                            <span class="inline-flex w-fit items-center gap-[5px] rounded-full border-[1.5px] border-[rgba(200,16,46,0.32)] bg-[rgba(200,16,46,0.08)] px-[14px] py-[7px] text-[11px] font-medium tracking-[0.04em] whitespace-nowrap text-brand rtl:font-arabic">
                                                                <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><polyline points="20,6 9,17 4,12" /></svg>{{ $key }}
                                                            </span>
                                                        @else
                                                            <span class="inline-flex w-fit items-center rounded-full border-[1.5px] border-ink-10 bg-[rgba(13,13,13,0.04)] px-[14px] py-[7px] text-[11px] font-medium tracking-[0.04em] whitespace-nowrap text-ink-30 line-through dark:bg-white/4 rtl:font-arabic">{{ $key }}</span>
                                                        @endif
                                                    @elseif ($key === 'price')
                                                        @if ($unit['price'])
                                                            <div class="{{ $value }} flex-col items-start! gap-0.5!">
                                                                <span class="font-sans text-[13px] tracking-[0.08em] text-ink-30 uppercase">{{ __('EGP') }}</span>
                                                                <span>{{ $unit['price'] }}</span>
                                                                @if ($isBest)
                                                                    <span class="{{ $badge }}">{{ __('Best Value') }}</span>
                                                                @endif
                                                            </div>
                                                        @else
                                                            <span class="{{ $text }} text-ink-30">—</span>
                                                        @endif
                                                    @elseif ($key === 'status')
                                                        @if ($unit['status'])
                                                            <span class="inline-flex w-fit items-center gap-[5px] rounded-full border-[1.5px] px-[14px] py-1.5 text-[11px] font-semibold tracking-[0.1em] uppercase rtl:font-arabic rtl:tracking-normal {{ $statusStyles[$unit['status']['style']] ?? $statusStyles['ink'] }}">{{ $unit['status']['label'] }}</span>
                                                        @else
                                                            <span class="{{ $text }} text-ink-30">—</span>
                                                        @endif
                                                    @elseif ($key === 'location')
                                                        <span class="flex items-start gap-1.5 text-[14px] leading-[1.4] text-ink-60 rtl:font-arabic max-[600px]:text-[13px]">
                                                            @if ($unit['location'])
                                                                {!! $pin !!}{{ $unit['location'] }}
                                                            @else
                                                                —
                                                            @endif
                                                        </span>
                                                    @elseif ($key === 'agent')
                                                        @if ($agent = $unit['agent'])
                                                            <div class="flex items-center gap-3">
                                                                @if ($agent['photo'])
                                                                    <img src="{{ $agent['photo'] }}" alt="{{ $agent['name'] }}" loading="lazy" class="size-10 shrink-0 rounded-full border-2 border-[rgba(200,16,46,0.4)] object-cover" />
                                                                @else
                                                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full border-2 border-[rgba(200,16,46,0.4)] text-[12px] font-bold text-brand" aria-hidden="true">{{ $agent['initials'] }}</span>
                                                                @endif
                                                                <div>
                                                                    <div class="text-[14px] font-medium text-ink rtl:font-arabic">{{ $agent['name'] }}</div>
                                                                    <div class="mt-px text-[10px] tracking-[0.1em] text-ink-30 uppercase rtl:font-arabic rtl:tracking-normal">{{ $agent['role'] ?: __('Agent') }}</div>
                                                                </div>
                                                            </div>
                                                        @else
                                                            <span class="{{ $text }} text-ink-30">—</span>
                                                        @endif
                                                    @elseif (in_array($key, ['beds', 'baths', 'area', 'floor'], true))
                                                        @php
                                                            [$unitLabel, $bestLabel] = ['beds' => [__('bd'), __('Most')], 'baths' => [__('ba'), __('Most')], 'area' => [__('m²'), __('Largest')], 'floor' => [__('fl'), __('Highest')]][$key];
                                                            $max = ['area' => $maxArea, 'floor' => $maxFloor][$key] ?? null;
                                                        @endphp
                                                        @if ($unit[$key] !== null)
                                                            <div class="{{ $value }}">
                                                                {{ $key === 'area' ? number_format($unit[$key]) : $unit[$key] }}<span class="{{ $unitText }}">{{ $unitLabel }}</span>
                                                                @if ($isBest)
                                                                    <span class="{{ $badge }}">{{ $bestLabel }}</span>
                                                                @endif
                                                            </div>
                                                            @if ($max !== null)
                                                                <div class="{{ $bar }}"><div class="{{ $barFill }}" x-data x-intersect.once.threshold.30="$el.style.width = '{{ $max > 0 ? round($unit[$key] / $max * 100) : 0 }}%'"></div></div>
                                                            @endif
                                                        @else
                                                            <span class="{{ $text }} text-ink-30">—</span>
                                                        @endif
                                                    @else
                                                        <span class="{{ $text }}">{{ $unit[$key] ?? '—' }}</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                </div>
            </main>

            {{-- Ready to decide --}}
            <section class="relative mt-16 overflow-hidden bg-[rgb(13,13,13)] py-20 before:pointer-events-none before:absolute before:inset-0 before:bg-[linear-gradient(135deg,rgba(200,16,46,0.09)_0%,transparent_55%)]">
                <div class="relative mx-auto max-w-[1320px] px-12 max-[900px]:px-6">
                    <h2 class="mb-2 text-center font-sans text-[clamp(28px,3.5vw,48px)] font-light text-white rtl:font-arabic [&_em]:text-brand [&_em]:not-italic">{!! __('Ready to <em>Decide?</em>') !!}</h2>
                    <p class="mb-12 text-center text-[13px] text-white/38 rtl:font-arabic">{{ __('Schedule a viewing or request detailed information from our dedicated agents.') }}</p>
                    <div class="flex flex-wrap gap-4 max-[480px]:flex-col">
                        @foreach ($units as $i => $unit)
                            @php($phone = $unit['agent']['phone'] ?? $business->hotline)
                            <div
                                x-data="{ shown: false }"
                                x-intersect.once.threshold.08="setTimeout(() => shown = true, {{ $i * 80 }})"
                                class="group min-w-[200px] flex-1 overflow-hidden rounded-2xl border border-white/7 bg-white/4 transition-all duration-[650ms] ease-out-ex hover:-translate-y-1 hover:border-[rgba(200,16,46,0.28)] hover:bg-white/6"
                                :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-[22px]'"
                            >
                                <img src="{{ $unit['image'] }}" alt="{{ $unit['title'] }}" loading="lazy" class="block aspect-video w-full object-cover opacity-80 transition-opacity duration-400 group-hover:opacity-100" />
                                <div class="p-[18px]">
                                    @if ($unit['type'])
                                        <div class="mb-2 text-[9px] font-bold tracking-[0.2em] text-brand uppercase rtl:font-arabic rtl:tracking-normal">{{ $unit['type'] }}</div>
                                    @endif
                                    <div class="mb-1 font-sans text-[22px] leading-[1.15] font-light text-white rtl:font-arabic">{{ $unit['title'] }}</div>
                                    <div class="mb-[18px] text-[14px] font-medium tracking-[0.01em] text-brand">{{ $unit['price'] ? __('EGP :price', ['price' => $unit['price']]) : '' }}</div>
                                    <div class="flex gap-2">
                                        <a href="{{ $unit['url'] }}" class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-full border border-white/22 bg-white/8 px-4 py-[11px] text-[11px] font-medium tracking-[0.06em] whitespace-nowrap text-white/80 uppercase backdrop-blur-[20px] backdrop-saturate-180 transition-all duration-300 ease-out-ex hover:-translate-y-px hover:border-white/40 hover:bg-white/14 hover:text-white dark:border-white/28 dark:bg-transparent dark:text-white dark:backdrop-blur-none rtl:font-arabic rtl:tracking-[0.02em] rtl:normal-case">{{ __('View Details') }}</a>
                                        @if ($phone)
                                            <a href="tel:{{ $phone }}" @click="$track('phone', { unit_id: {{ $unit['id'] }} })" class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-full border border-white/20 bg-[linear-gradient(135deg,rgba(215,20,52,0.96)_0%,rgb(185,10,36)_100%)] px-4 py-[11px] text-[11px] font-bold tracking-[0.06em] whitespace-nowrap text-white uppercase backdrop-blur-[20px] transition-all duration-300 ease-out-ex hover:-translate-y-0.5 hover:bg-[linear-gradient(135deg,rgba(225,25,58,0.98)_0%,rgb(200,16,46)_100%)] rtl:font-arabic rtl:tracking-[0.02em] rtl:normal-case">{{ __('Request Tour') }}</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @else
            {{-- Nothing to compare --}}
            <main class="flex flex-col items-center justify-center px-12 py-[140px] text-center max-[900px]:px-6 max-[900px]:py-20">
                <svg width="56" height="56" fill="none" stroke="currentColor" stroke-width="1.3" viewBox="0 0 24 24" aria-hidden="true" class="mb-6 text-ink-30"><line x1="18" y1="20" x2="18" y2="10" /><line x1="12" y1="20" x2="12" y2="4" /><line x1="6" y1="20" x2="6" y2="14" /></svg>
                <h2 class="mb-3 font-sans text-[40px] font-light text-ink rtl:font-arabic max-[600px]:text-[28px]">{{ __('Nothing to Compare') }}</h2>
                <p class="mb-8 max-w-[380px] text-[14px] text-ink-60 rtl:font-arabic">{{ __('Select two or more properties from the listings page and tap "Compare Now" to see them side-by-side.') }}</p>
                <a href="{{ route('properties') }}" class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-[linear-gradient(135deg,rgba(215,20,52,0.96)_0%,rgb(185,10,36)_100%)] px-7 py-[13px] text-[12px] font-semibold text-white backdrop-blur-[20px] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[linear-gradient(135deg,rgba(225,25,58,0.98)_0%,rgb(200,16,46)_100%)] rtl:font-arabic">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true" class="rtl:-scale-x-100"><polyline points="15,18 9,12 15,6" /></svg>
                    {{ __('Browse Properties') }}
                </a>
            </main>
        @endif
    </div>
</x-website.layout>
