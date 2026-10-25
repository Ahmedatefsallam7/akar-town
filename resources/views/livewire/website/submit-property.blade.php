{{--
    The navbar's "Submit Property" window (App\Livewire\Website\SubmitProperty), as the design's submit-modal.css, with
    a dark side panel (not in the design; the auth window's look) for Settings → Submit Property: the short text and the
    points. Opened by the window event "open-submit-property"; Escape, the × and a click outside close it. The panel is
    hidden on phones.
--}}
@php
    $label = 'text-[11px] font-semibold tracking-[0.08em] text-black/50 uppercase rtl:font-arabic rtl:tracking-normal dark:text-[rgba(237,232,223,0.4)]';
    $input = 'w-full rounded-[10px] border-[1.5px] border-black/10 bg-white/70 px-[14px] py-[11px] font-sans text-[13.5px] font-medium text-ink outline-none transition-[border-color,box-shadow] duration-200 placeholder:text-black/28 focus:border-brand focus:shadow-[0_0_0_3px_rgba(200,16,46,0.08)] dark:border-white/10 dark:bg-white/5 dark:text-[#ede8df] dark:placeholder:text-[rgba(237,232,223,0.2)] dark:focus:shadow-[0_0_0_3px_rgba(200,16,46,0.12)]';
    $select = $input.' cursor-pointer appearance-none pe-[34px] [&_option]:text-ink dark:[&_option]:bg-[#1a1814] dark:[&_option]:text-[#ede8df]';
    $chevron = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="pointer-events-none absolute end-3 top-1/2 -translate-y-1/2 text-[#888]"><polyline points="6 9 12 15 18 9" /></svg>';
    $affix = 'pointer-events-none absolute top-1/2 -translate-y-1/2 text-[11px] font-bold tracking-[0.05em] text-black/38 dark:text-[rgba(237,232,223,0.3)]';
    $error = 'block text-[12px] text-danger rtl:font-arabic';
    $field = 'flex flex-col gap-1.5';
@endphp

<div
    x-data="{
        open: false,
        show() {
            this.open = true
            document.body.style.overflow = 'hidden'
            $wire.ready || $wire.open()
        },
        close() {
            this.open = false
            document.body.style.overflow = ''
        },
    }"
    @open-submit-property.window="show()"
    @keydown.escape.window="open && close()"
    x-show="open"
    x-cloak
    x-transition:enter="transition-opacity duration-280 ease-out"
    x-transition:enter-start="opacity-0"
    x-transition:leave="transition-opacity duration-280 ease-out"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-9998 flex items-center justify-center bg-[rgba(8,8,8,0.65)] p-4 backdrop-blur-[6px]"
    @click.self="close()"
    role="dialog"
    aria-modal="true"
    aria-labelledby="sp-title"
>
    <div
        x-show="open"
        x-transition:enter="transition-transform duration-320 ease-out-ex"
        x-transition:enter-start="[transform:translateY(20px)_scale(0.97)]"
        class="relative flex max-h-[90vh] w-full max-w-[880px] overflow-hidden rounded-[20px] border border-black/8 shadow-[0_32px_80px_rgba(0,0,0,0.18)] dark:border-white/8 max-md:max-w-[560px]"
    >
        <button type="button" @click="close()" class="absolute top-[14px] right-[14px] z-2 flex size-8 cursor-pointer items-center justify-center rounded-full border border-black/10 text-ink transition-[background] duration-200 hover:bg-black/6 rtl:right-auto rtl:left-[14px] dark:border-white/10 dark:text-[rgba(237,232,223,0.8)] dark:hover:bg-white/6" aria-label="{{ __('Close') }}">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
        </button>

        {{-- Side panel: why sell with us (Settings → Submit Property) --}}
        @if ($intro || $points)
            <div class="relative flex w-[320px] flex-none flex-col overflow-x-hidden overflow-y-auto bg-[linear-gradient(160deg,#111_0%,#1c1c1c_60%,#0d0d0d_100%)] px-8 pt-11 pb-10 before:pointer-events-none before:absolute before:-top-[60px] before:-left-[60px] before:size-[220px] before:rounded-full before:bg-[radial-gradient(circle,rgba(200,16,46,0.18)_0%,transparent_70%)] max-md:hidden">
                <p class="relative mb-3 text-[10px] font-bold tracking-[0.22em] text-brand uppercase rtl:font-arabic rtl:tracking-normal">{{ __('Why Sell With Us') }}</p>
                @if ($intro)
                    <p class="relative mb-7 text-[13px] leading-[1.65] text-white/65 rtl:font-arabic">{{ $intro }}</p>
                @endif
                <ul class="relative flex list-none flex-col gap-5 p-0">
                    @foreach ($points as $point)
                        <li class="flex items-start gap-3">
                            <span class="grid size-10 flex-none place-items-center overflow-hidden rounded-xl bg-white/6" aria-hidden="true">
                                @if ($point['icon'] ?? null)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($point['icon']) }}" alt="" loading="lazy" class="size-6 object-contain" />
                                @else
                                    <span class="size-1.5 rounded-full bg-brand"></span>
                                @endif
                            </span>
                            <span>
                                <span class="mb-1 block text-[13.5px] font-semibold text-white rtl:font-arabic">{{ $point['header_'.app()->getLocale()] }}</span>
                                <span class="block text-[12px] leading-[1.55] text-white/55 rtl:font-arabic">{{ $point['body_'.app()->getLocale()] ?? '' }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- The design's window --}}
        <div class="flex min-w-0 flex-1 flex-col bg-page dark:bg-[#1a1814]">
            <div class="shrink-0 px-7 pt-7 max-[480px]:px-5 max-[480px]:pt-[22px]">
                <div class="mb-[14px] flex size-11 items-center justify-center rounded-xl bg-[rgba(200,16,46,0.08)] text-brand">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" /><polyline points="9 22 9 12 15 12 15 22" /></svg>
                </div>
                <h2 id="sp-title" class="mb-1 text-[20px] font-bold text-ink rtl:font-arabic dark:text-[#ede8df]">{{ __('Submit Your Property') }}</h2>
                <p class="mb-[22px] text-[13px] text-black/50 rtl:font-arabic dark:text-[rgba(237,232,223,0.45)]">{{ __('Fill in your unit details and our team will be in touch.') }}</p>
            </div>
            <div class="mx-7 h-px bg-black/7 max-[480px]:mx-5 dark:bg-white/7"></div>

            <div class="flex-1 overflow-y-auto px-7 pt-5 pb-7 [&::-webkit-scrollbar]:w-1 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-black/15 dark:[&::-webkit-scrollbar-thumb]:bg-white/15 max-[480px]:px-5 max-[480px]:pt-4 max-[480px]:pb-5">
                @if ($sent)
                    <div class="flex flex-col items-center gap-[14px] px-7 pt-[52px] pb-10 text-center" role="status">
                        <div class="flex size-[76px] animate-pop items-center justify-center rounded-full bg-[rgba(25,160,70,0.1)] text-[#19a046]">
                            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12" /></svg>
                        </div>
                        <h3 class="mt-1 text-[22px] font-bold text-ink rtl:font-arabic dark:text-[#ede8df]">{{ __('Submitted!') }}</h3>
                        <p class="text-[14px] leading-[1.65] text-black/50 rtl:font-arabic dark:text-[rgba(237,232,223,0.5)]">{!! __('Thanks, we’ll review your unit<br>and get back to you shortly.') !!}</p>
                        <button type="button" wire:click="again" class="mt-2 cursor-pointer rounded-full border-[1.5px] border-black/12 px-7 py-[11px] text-[13px] font-semibold text-ink transition-[border-color,color] duration-200 hover:border-brand hover:text-brand rtl:font-arabic dark:border-[rgba(237,232,223,0.15)] dark:text-[rgba(237,232,223,0.8)]">{{ __('Submit Another') }}</button>
                    </div>
                @elseif (! $ready)
                    <div class="flex justify-center py-16" aria-hidden="true"><span class="size-7 animate-spin rounded-full border-2 border-black/10 border-t-brand dark:border-white/10 dark:border-t-brand"></span></div>
                @else
                    <form wire:submit="send" class="flex flex-col gap-4" novalidate>
                        <div class="grid grid-cols-[repeat(2,minmax(0,1fr))] gap-3 max-[480px]:grid-cols-1">
                            <div class="{{ $field }}">
                                <label for="sp-name" class="{{ $label }}">{{ __('Full Name') }}</label>
                                <input id="sp-name" type="text" wire:model="fullName" autocomplete="name" class="{{ $input }} rtl:font-arabic" placeholder="{{ __('Your full name') }}" />
                                @error('fullName') <span class="{{ $error }}">{{ $message }}</span> @enderror
                            </div>
                            <div class="{{ $field }}">
                                <label for="sp-phone" class="{{ $label }}">{{ __('Phone Number') }}</label>
                                <input id="sp-phone" type="tel" wire:model="phone" autocomplete="tel" dir="ltr" class="{{ $input }} rtl:text-right" placeholder="+20 1XX XXX XXXX" />
                                @error('phone') <span class="{{ $error }}">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="{{ $field }}">
                            <label for="sp-type" class="{{ $label }}">{{ __('Property Type') }}</label>
                            <div class="relative">
                                <select id="sp-type" wire:model="type" class="{{ $select }} rtl:font-arabic" :class="! $wire.type && 'text-black/28 dark:text-[rgba(237,232,223,0.2)]'">
                                    <option value="" disabled>{{ __('Select type…') }}</option>
                                    @foreach ($types as $slug => $name)
                                        <option value="{{ $slug }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                {!! $chevron !!}
                            </div>
                            @error('type') <span class="{{ $error }}">{{ $message }}</span> @enderror
                        </div>

                        <div class="{{ $field }}">
                            <span id="sp-listing" class="{{ $label }}">{{ __('Listing Type') }}</span>
                            <div class="flex overflow-hidden rounded-[10px] border-[1.5px] border-black/10 dark:border-white/10" role="radiogroup" aria-labelledby="sp-listing">
                                @foreach (['sale' => __('For Sale'), 'rent' => __('For Rent')] as $value => $name)
                                    <button
                                        type="button"
                                        role="radio"
                                        :aria-checked="$wire.listing === @js($value)"
                                        @click="$wire.listing = @js($value)"
                                        class="flex-1 cursor-pointer px-[14px] py-[11px] text-[13px] font-semibold transition-[background,color] duration-200 not-first:border-s-[1.5px] not-first:border-black/10 rtl:font-arabic dark:not-first:border-white/10"
                                        :class="$wire.listing === @js($value) ? 'bg-brand text-white' : 'text-black/45 dark:text-[rgba(237,232,223,0.4)]'"
                                    >{{ $name }}</button>
                                @endforeach
                            </div>
                            @error('listing') <span class="{{ $error }}">{{ $message }}</span> @enderror
                        </div>

                        <div class="{{ $field }}">
                            <label for="sp-compound" class="{{ $label }}">{{ __('Area / Compound') }}</label>
                            <input id="sp-compound" type="text" wire:model="compound" class="{{ $input }} rtl:font-arabic" placeholder="{{ __('e.g. Katameya Heights') }}" />
                            @error('compound') <span class="{{ $error }}">{{ $message }}</span> @enderror
                        </div>

                        {{-- The place: the admin's locations tree, or a typed address when it isn't there --}}
                        @if (! $manual)
                            <div class="grid grid-cols-[repeat(2,minmax(0,1fr))] gap-3 max-[480px]:grid-cols-1">
                                @foreach ($levels as $level => $options)
                                    <div class="{{ $field }}" wire:key="sp-level-{{ $level }}">
                                        <label for="sp-place-{{ $level }}" class="{{ $label }}">{{ [__('Country'), __('City'), __('Region'), __('Neighbourhood'), __('Sub-area')][$level] }}</label>
                                        <div class="relative">
                                            <select id="sp-place-{{ $level }}" wire:model.live="place.{{ $level }}" class="{{ $select }} rtl:font-arabic" :class="! $wire.place[{{ $level }}] && 'text-black/28 dark:text-[rgba(237,232,223,0.2)]'">
                                                <option value="">{{ __('Select…') }}</option>
                                                @foreach ($options as $id => $name)
                                                    <option value="{{ $id }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                            {!! $chevron !!}
                                        </div>
                                        @error('place.'.$level) <span class="{{ $error }}">{{ $message }}</span> @enderror
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="{{ $field }}">
                                <label for="sp-address" class="{{ $label }}">{{ __('Full address') }}</label>
                                <input id="sp-address" type="text" wire:model="fullAddress" class="{{ $input }} rtl:font-arabic" placeholder="{{ __('Street, district, city') }}" />
                                @error('fullAddress') <span class="{{ $error }}">{{ $message }}</span> @enderror
                            </div>
                        @endif
                        <button type="button" wire:click="$toggle('manual')" class="-mt-2 cursor-pointer self-start text-[12px] font-semibold text-brand underline underline-offset-3 transition-colors duration-200 hover:text-[#a50d26] rtl:font-arabic">
                            {{ $manual ? __('Choose my area from the list') : __('My area isn’t listed — type my address') }}
                        </button>

                        <div class="{{ $field }}">
                            <label for="sp-price" class="{{ $label }}">{{ __('Price') }}</label>
                            <div class="relative" dir="ltr">
                                <span class="{{ $affix }} left-[14px]">{{ __('EGP') }}</span>
                                <input id="sp-price" type="number" min="0" wire:model="price" class="{{ $input }} ps-[46px]" placeholder="0" />
                            </div>
                            @error('price') <span class="{{ $error }}">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-[repeat(2,minmax(0,1fr))] gap-3 max-[480px]:grid-cols-1">
                            <div class="{{ $field }}">
                                <label for="sp-beds" class="{{ $label }}">{{ __('Bedrooms') }}</label>
                                <input id="sp-beds" type="number" min="0" max="20" wire:model="bedrooms" class="{{ $input }}" placeholder="0" />
                                @error('bedrooms') <span class="{{ $error }}">{{ $message }}</span> @enderror
                            </div>
                            <div class="{{ $field }}">
                                <label for="sp-baths" class="{{ $label }}">{{ __('Bathrooms') }}</label>
                                <input id="sp-baths" type="number" min="0" max="20" wire:model="bathrooms" class="{{ $input }}" placeholder="0" />
                                @error('bathrooms') <span class="{{ $error }}">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="{{ $field }}">
                            <label for="sp-area" class="{{ $label }}">{{ __('Unit Area') }}</label>
                            <div class="relative" dir="ltr">
                                <input id="sp-area" type="number" min="0" wire:model="unitArea" class="{{ $input }} pe-[46px]" placeholder="0" />
                                <span class="{{ $affix }} right-[14px] text-[12px] font-semibold tracking-normal">{{ __('m²') }}</span>
                            </div>
                            @error('unitArea') <span class="{{ $error }}">{{ $message }}</span> @enderror
                        </div>

                        <div class="{{ $field }}">
                            <span class="{{ $label }}">{{ __('Photos') }}</span>
                            <div class="relative cursor-pointer rounded-[10px] border-[1.5px] border-dashed border-black/15 px-4 pt-[22px] pb-4 text-center transition-[border-color,background] duration-200 hover:border-brand hover:bg-[rgba(200,16,46,0.03)] dark:border-white/12">
                                @if (count($photos) < \Modules\UnitRequests\Models\UnitRequest::MAX_PHOTOS)
                                    <input type="file" wire:model="photos" multiple accept="image/jpeg,image/png,image/webp" class="absolute inset-0 size-full cursor-pointer opacity-0" aria-label="{{ __('Upload property photos') }}" />
                                @endif
                                <div class="mb-2 flex justify-center text-black/28 dark:text-[rgba(237,232,223,0.22)]" aria-hidden="true">
                                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" /><circle cx="8.5" cy="8.5" r="1.5" /><polyline points="21 15 16 10 5 21" /></svg>
                                </div>
                                <div class="text-[13px] font-semibold text-ink rtl:font-arabic dark:text-[#ede8df]">{{ __('Click or drag photos here') }}</div>
                                <div class="mt-0.5 text-[11.5px] text-black/38 rtl:font-arabic dark:text-[rgba(237,232,223,0.35)]">{{ __('JPG, PNG, WEBP · Up to 10 images') }}</div>
                                <div wire:loading wire:target="photos" class="mt-2 text-[11.5px] text-black/45 rtl:font-arabic dark:text-white/45">{{ __('Uploading…') }}</div>
                                @if ($photos)
                                    <div class="relative z-1 mt-[14px] flex flex-wrap justify-center gap-2">
                                        @foreach ($photos as $i => $photo)
                                            <div class="relative" wire:key="sp-photo-{{ $i }}">
                                                @if (method_exists($photo, 'isPreviewable') && $photo->isPreviewable())
                                                    <img src="{{ $photo->temporaryUrl() }}" alt="" class="size-[60px] rounded-lg border border-black/8 object-cover" />
                                                @else
                                                    <span class="flex size-[60px] items-center justify-center rounded-lg border border-black/8 text-[10px] text-black/40">{{ $i + 1 }}</span>
                                                @endif
                                                <button type="button" wire:click="removePhoto({{ $i }})" class="absolute -top-1.5 -right-1.5 flex size-5 cursor-pointer items-center justify-center rounded-full bg-ink text-paper" aria-label="{{ __('Remove photo') }}">
                                                    <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            @error('photos') <span class="{{ $error }}">{{ $message }}</span> @enderror
                            @error('photos.*') <span class="{{ $error }}">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" wire:loading.attr="disabled" wire:target="send,photos" class="mt-1.5 w-full cursor-pointer rounded-full bg-brand p-[14px] text-[14px] font-[650] tracking-[0.04em] text-white transition-[background,translate] duration-250 hover:-translate-y-px hover:bg-[#a50d26] disabled:opacity-60 rtl:font-arabic">{{ __('Submit Property') }}</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
