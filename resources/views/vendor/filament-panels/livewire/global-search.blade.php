{{--
    The admin's global search (Filament's view, redesigned; the behaviour is Filament's: the field, opening, closing,
    arrow keys). The window: "Results for …" with the count, each group with its resource's icon, colour and count,
    each result with a coloured initial, the searched text marked and its details, and the keyboard keys at the bottom.
    Groups and icons come from App\Filament\Support\AdminGlobalSearchProvider; the field's look is in theme.css.
--}}
@php
    use App\Filament\Support\AdminGlobalSearchProvider;
    use Filament\Support\Facades\FilamentView;
    use Filament\Support\Icons\Heroicon;
    use Filament\View\PanelsIconAlias;
    use Filament\View\PanelsRenderHook;

    $debounce = filament()->getGlobalSearchDebounce();
    $keyBindings = filament()->getGlobalSearchKeyBindings();
    $suffix = filament()->getGlobalSearchFieldSuffix();

    // One colour per group (full class names, so Tailwind finds them).
    $palette = [
        ['tile' => 'bg-indigo-50 text-indigo-600 ring-indigo-200/70', 'initial' => 'from-indigo-500 to-violet-500'],
        ['tile' => 'bg-emerald-50 text-emerald-600 ring-emerald-200/70', 'initial' => 'from-emerald-500 to-teal-500'],
        ['tile' => 'bg-amber-50 text-amber-600 ring-amber-200/70', 'initial' => 'from-amber-500 to-orange-500'],
        ['tile' => 'bg-rose-50 text-rose-600 ring-rose-200/70', 'initial' => 'from-rose-500 to-pink-500'],
        ['tile' => 'bg-sky-50 text-sky-600 ring-sky-200/70', 'initial' => 'from-sky-500 to-cyan-500'],
        ['tile' => 'bg-violet-50 text-violet-600 ring-violet-200/70', 'initial' => 'from-violet-500 to-fuchsia-500'],
    ];
    $kbdLook = 'min-w-5 items-center justify-center rounded-md border border-slate-200 bg-white px-1.5 py-0.5 font-sans text-[10px] font-semibold text-slate-500 shadow-[0_1px_0_rgb(15_23_42/0.08)]';
    $kbd = 'inline-flex items-center justify-center '.$kbdLook;
@endphp

<div class="fi-global-search-ctn">
    {{ FilamentView::renderHook(PanelsRenderHook::GLOBAL_SEARCH_START) }}

    <div
        x-on:focus-first-global-search-result.stop="$el.querySelector('.fi-global-search-result-link')?.focus()"
        class="fi-global-search"
    >
        <div x-id="['input']" class="fi-global-search-field">
            <label x-bind:for="$id('input')" class="fi-sr-only">
                {{ __('filament-panels::global-search.field.label') }}
            </label>

            <x-filament::input.wrapper
                :prefix-icon="Heroicon::MagnifyingGlass"
                :prefix-icon-alias="PanelsIconAlias::GLOBAL_SEARCH_FIELD"
                inline-prefix
                :suffix="$suffix"
                inline-suffix
                wire:target="search"
            >
                <input
                    autocomplete="off"
                    maxlength="1000"
                    placeholder="{{ __('admin.global_search.placeholder') }}"
                    type="search"
                    wire:key="global-search.field.input"
                    x-bind:id="$id('input')"
                    x-on:keydown.down.prevent.stop="$dispatch('focus-first-global-search-result')"
                    wire:model.live.debounce.{{ $debounce }}="search"
                    x-mousetrap.global.{{ collect($keyBindings)->map(fn (string $keyBinding): string => str_replace('+', '-', $keyBinding))->implode('.') }}="document.getElementById($id('input'))?.focus()"
                    class="fi-input fi-input-has-inline-prefix"
                />
            </x-filament::input.wrapper>
        </div>

        @if ($results !== null)
            @php
                $categories = $results->getCategories();
                $total = $categories->sum(fn ($items) => count($items));
            @endphp

            <div
                x-data="{
                    isOpen: false,
                    open(event) {
                        this.isOpen = true
                    },
                    close(event) {
                        this.isOpen = false
                    },
                }"
                x-init="$nextTick(() => open())"
                x-on:click.away="close()"
                x-on:keydown.escape.window="close()"
                x-on:keydown.up.prevent="$focus.wrap().previous()"
                x-on:keydown.down.prevent="$focus.wrap().next()"
                x-on:open-global-search-results.window="$nextTick(() => open())"
                x-show="isOpen"
                x-transition:enter-start="fi-transition-enter-start"
                x-transition:leave-end="fi-transition-leave-end"
                class="fi-global-search-results-ctn"
            >
                @if ($categories->isEmpty())
                    <div class="flex flex-col items-center px-6 py-10 text-center">
                        <span class="relative mb-4 grid size-14 place-items-center rounded-2xl bg-gradient-to-br from-indigo-50 to-violet-100 text-indigo-500 ring-1 ring-indigo-100">
                            <x-filament::icon icon="heroicon-o-magnifying-glass" class="size-6" />
                            <span class="absolute -end-1 -top-1 grid size-5 place-items-center rounded-full bg-white text-[11px] font-bold text-rose-500 shadow ring-1 ring-rose-100">?</span>
                        </span>
                        <p class="text-sm font-semibold text-slate-800">{{ __('admin.global_search.no_results', ['search' => $this->search]) }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ __('admin.global_search.no_results_hint') }}</p>
                    </div>
                @else
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-gradient-to-r from-indigo-50/80 via-white to-white px-4 py-3">
                        <p class="min-w-0 truncate text-xs text-slate-500">
                            {{ __('admin.global_search.results_for') }}
                            <span class="font-semibold text-slate-800">{{ __('admin.global_search.quoted', ['search' => $this->search]) }}</span>
                        </p>
                        <span class="shrink-0 rounded-full bg-indigo-600 px-2.5 py-0.5 text-[11px] font-semibold text-white shadow-[0_4px_10px_-4px_rgb(79_70_229/0.7)]">
                            {{ trans_choice('admin.global_search.count', $total, ['count' => $total]) }}
                        </span>
                    </div>

                    <ul class="fi-global-search-results min-h-0 flex-1 overflow-y-auto overscroll-contain p-2">
                        @foreach ($categories as $group => $groupedResults)
                            @php
                                $icon = AdminGlobalSearchProvider::icon($group);
                                // The colour follows the resource (its icon), so it's the same in both languages.
                                $colour = $palette[crc32($icon instanceof \BackedEnum ? (string) $icon->value : ($icon instanceof \Illuminate\Contracts\Support\Htmlable ? $icon->toHtml() : $icon)) % count($palette)];
                            @endphp

                            <li class="fi-global-search-result-group">
                                <h3 class="fi-global-search-result-group-header">
                                    <span @class(['grid size-6 shrink-0 place-items-center rounded-lg ring-1', $colour['tile']])>
                                        <x-filament::icon :icon="$icon" class="size-3.5" />
                                    </span>
                                    <span class="min-w-0 truncate">{{ $group }}</span>
                                    <span class="ms-auto shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">{{ count($groupedResults) }}</span>
                                </h3>

                                <ul class="fi-global-search-result-group-results">
                                    @foreach ($groupedResults as $result)
                                        @php
                                            $resultVisibleActions = $result->getVisibleActions();
                                            $plainTitle = trim(strip_tags($result->title instanceof \Illuminate\Contracts\Support\Htmlable ? $result->title->toHtml() : $result->title));
                                        @endphp

                                        <li @class(['fi-global-search-result group/result', 'fi-global-search-result-has-actions' => $resultVisibleActions])>
                                            <a
                                                {{ \Filament\Support\generate_href_html($result->url) }}
                                                x-on:click="close()"
                                                class="fi-global-search-result-link"
                                            >
                                                <span @class(['grid size-9 shrink-0 place-items-center rounded-xl bg-gradient-to-br text-sm font-bold text-white shadow-[0_6px_14px_-8px_rgb(15_23_42/0.6)] transition-transform duration-200 group-hover/result:scale-105', $colour['initial']])>
                                                    {{ mb_strtoupper(mb_substr($plainTitle, 0, 1)) }}
                                                </span>

                                                <div class="min-w-0 flex-1">
                                                    <h4 class="fi-global-search-result-heading">
                                                        {{ AdminGlobalSearchProvider::highlight($result->title, $this->search) }}
                                                    </h4>

                                                    @if ($result->details)
                                                        <dl class="fi-global-search-result-details">
                                                            @foreach ($result->details as $label => $value)
                                                                <div class="fi-global-search-result-detail">
                                                                    @if (! array_is_list($result->details))
                                                                        <dt class="fi-global-search-result-detail-label">{{ $label }}:</dt>
                                                                    @endif
                                                                    <dd class="fi-global-search-result-detail-value">{{ $value }}</dd>
                                                                </div>
                                                            @endforeach
                                                        </dl>
                                                    @endif
                                                </div>

                                                <span class="flex shrink-0 items-center gap-1.5">
                                                    <kbd class="{{ $kbdLook }} hidden items-center justify-center group-focus-within/result:inline-flex">↵</kbd>
                                                    <x-filament::icon
                                                        icon="heroicon-m-chevron-right"
                                                        class="size-4 -translate-x-1 text-slate-300 opacity-0 transition-all duration-200 group-hover/result:translate-x-0 group-hover/result:text-indigo-500 group-hover/result:opacity-100 rtl:translate-x-1 rtl:rotate-180 rtl:group-hover/result:translate-x-0"
                                                    />
                                                </span>
                                            </a>

                                            @if ($resultVisibleActions)
                                                <div class="fi-global-search-result-actions">
                                                    @foreach ($resultVisibleActions as $action)
                                                        {{ $action }}
                                                    @endforeach
                                                </div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endforeach
                    </ul>

                    <div class="hidden items-center gap-4 border-t border-slate-100 bg-slate-50/70 px-4 py-2.5 text-[11px] text-slate-500 sm:flex">
                        <span class="flex items-center gap-1"><kbd class="{{ $kbd }}">↑</kbd><kbd class="{{ $kbd }}">↓</kbd> {{ __('admin.global_search.navigate') }}</span>
                        <span class="flex items-center gap-1"><kbd class="{{ $kbd }}">↵</kbd> {{ __('admin.global_search.open') }}</span>
                        <span class="flex items-center gap-1"><kbd class="{{ $kbd }}">esc</kbd> {{ __('admin.global_search.close') }}</span>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{ FilamentView::renderHook(PanelsRenderHook::GLOBAL_SEARCH_END) }}
</div>
