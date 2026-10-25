{{--
    The careers page's "Apply Now" window (App\Livewire\Website\JobApplication), as the design's #applyModal. Opened by
    the window event "apply-job" ({ id, title, department }): the title shows at once, the form is reset for that job.
    Escape, the × and a click outside close it.
--}}
@php
    $label = 'mb-2 text-[9px] font-bold tracking-[0.22em] text-ink-60 uppercase transition-colors duration-200 group-focus-within/field:text-brand rtl:font-arabic rtl:tracking-[0.04em]';
    $field = 'w-full appearance-none rounded-[14px] border-[1.5px] border-[rgba(13,13,13,0.16)] bg-white px-4 py-[13px] font-sans text-[14px] leading-[normal] text-ink outline-none [transition:border-color_0.25s,box-shadow_0.25s] placeholder:font-light placeholder:text-[rgba(13,13,13,0.42)] dark:placeholder:text-white/38 hover:border-[rgba(13,13,13,0.3)] focus:border-brand focus:shadow-[0_0_0_4px_rgba(200,16,46,0.09)] rtl:font-arabic dark:border-white/16 dark:bg-white/5 dark:focus:bg-white/7';
    $error = 'mt-1.5 block text-[12px] text-danger rtl:font-arabic';
@endphp

<div
    x-data="{
        open: false,
        title: '',
        department: '',
        file: '',
        show(job) {
            this.title = job.title
            this.department = job.department ?? ''
            this.file = ''
            this.open = true
            document.body.style.overflow = 'hidden'
            $wire.choose(job.id)
        },
        close() {
            this.open = false
            document.body.style.overflow = ''
        },
    }"
    @apply-job.window="show($event.detail)"
    @keydown.escape.window="open && close()"
    x-show="open"
    x-cloak
    x-transition:enter="transition-opacity duration-300 ease-out-ex"
    x-transition:enter-start="opacity-0"
    x-transition:leave="transition-opacity duration-300 ease-out-ex"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-500 flex items-center justify-center p-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="apply-title"
>
    <div @click="close()" class="absolute inset-0 bg-[rgba(7,6,10,0.72)] backdrop-blur-[6px]" aria-hidden="true"></div>

    <div
        x-show="open"
        x-transition:enter="transition-transform duration-350 ease-out-ex"
        x-transition:enter-start="[transform:translateY(20px)_scale(0.97)]"
        class="relative z-1 max-h-[90vh] w-full max-w-[580px] overflow-y-auto rounded-[28px] border border-[rgba(13,13,13,0.07)] bg-page shadow-[0_24px_80px_rgba(7,6,10,0.4),0_0_0_1px_rgba(255,255,255,0.06)] dark:bg-[#131108] [&::-webkit-scrollbar]:w-1 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-[rgba(13,13,13,0.14)] hover:[&::-webkit-scrollbar-thumb]:bg-[rgba(13,13,13,0.28)] [&::-webkit-scrollbar-track]:my-7 [&::-webkit-scrollbar-track]:bg-transparent"
    >
        <div class="flex items-start justify-between gap-4 px-9 pt-8 max-md:px-6 max-md:pt-6">
            <div>
                <p class="mb-2 text-[9px] font-bold tracking-[0.28em] text-brand uppercase rtl:font-arabic rtl:tracking-normal" x-text="department"></p>
                <h2 id="apply-title" class="font-sans text-[clamp(22px,3vw,30px)] leading-[1.1] font-light tracking-[-0.02em] text-ink rtl:font-arabic rtl:leading-[1.3] rtl:tracking-normal" x-text="title"></h2>
            </div>
            <button type="button" @click="close()" class="mt-1 grid size-9 shrink-0 cursor-pointer place-items-center rounded-full border-[1.5px] border-[rgba(13,13,13,0.12)] text-ink-60 transition-[background,border-color,color] duration-200 hover:border-[rgba(200,16,46,0.35)] hover:bg-[rgba(200,16,46,0.07)] hover:text-brand dark:border-white/12" aria-label="{{ __('Close') }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
            </button>
        </div>
        <div class="mx-9 mt-6 h-px bg-ink-10 max-md:mx-6 max-md:mt-5 dark:bg-white/8"></div>

        <div class="flex flex-col px-9 pt-7 pb-9 max-md:p-6">
            @if ($sent)
                <p class="rounded-[14px] border-[1.5px] border-[rgba(13,13,13,0.16)] bg-white px-4 py-[13px] text-[14px] leading-[1.6] text-ink rtl:font-arabic dark:border-white/16 dark:bg-white/5" role="status">
                    {{ __("Application sent — we'll review it within 5 business days.") }}
                </p>
            @else
                <form wire:submit="send" novalidate>
                    <div class="grid grid-cols-2 gap-5 max-md:grid-cols-1 max-md:gap-0">
                        <div class="group/field mb-[22px] flex flex-col">
                            <label for="af-name" class="{{ $label }}">{{ __('Full Name') }}</label>
                            <input id="af-name" type="text" wire:model="fullName" autocomplete="name" class="{{ $field }}" placeholder="{{ __('Your full name') }}" />
                            @error('fullName') <span class="{{ $error }}">{{ $message }}</span> @enderror
                        </div>
                        <div class="group/field mb-[22px] flex flex-col">
                            <label for="af-phone" class="{{ $label }}">{{ __('Phone') }}</label>
                            <input id="af-phone" type="tel" wire:model="phone" autocomplete="tel" dir="ltr" class="{{ $field }} rtl:text-right" placeholder="+20 100 000 0000" />
                            @error('phone') <span class="{{ $error }}">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="group/field mb-[22px] flex flex-col">
                        <label for="af-email" class="{{ $label }}">{{ __('Email Address') }}</label>
                        <input id="af-email" type="email" wire:model="email" autocomplete="email" class="{{ $field }}" placeholder="you@example.com" />
                        @error('email') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-[22px] flex flex-col" role="radiogroup" aria-labelledby="af-gender">
                        <span id="af-gender" class="{{ $label }}">{{ __('Gender') }} <span class="font-normal tracking-normal normal-case opacity-50">{{ __('(optional)') }}</span></span>
                        <div class="grid grid-cols-2 gap-[10px]">
                            @foreach (\Modules\Careers\Models\Applicant::genderOptions() as $value => $name)
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model="gender" value="{{ $value }}" class="peer sr-only" />
                                    <span class="flex items-center justify-center rounded-[14px] border-[1.5px] border-[rgba(13,13,13,0.16)] bg-white px-4 py-[13px] text-[14px] leading-[normal] text-ink-60 transition-[border-color,box-shadow,color] duration-250 peer-checked:border-brand peer-checked:font-semibold peer-checked:text-brand peer-checked:shadow-[0_0_0_4px_rgba(200,16,46,0.09)] peer-focus-visible:border-brand hover:border-[rgba(13,13,13,0.3)] rtl:font-arabic dark:border-white/16 dark:bg-white/5">{{ $name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('gender') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>

                    <div class="group/field mb-[22px] flex flex-col">
                        <label for="af-experience" class="{{ $label }}">{{ __('Years of Experience') }}</label>
                        <input id="af-experience" type="text" wire:model="experience" class="{{ $field }}" placeholder="{{ __('e.g. 3 years in real estate') }}" />
                        @error('experience') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>

                    <div class="group/field mb-[22px] flex flex-col">
                        <label for="af-linkedin" class="{{ $label }}">{{ __('LinkedIn Profile') }} <span class="font-normal tracking-normal normal-case opacity-50">{{ __('(optional)') }}</span></label>
                        <input id="af-linkedin" type="url" wire:model="linkedin" autocomplete="url" dir="ltr" class="{{ $field }} rtl:text-right" placeholder="https://www.linkedin.com/in/your-name" />
                        @error('linkedin') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>

                    <div class="group/field mb-[22px] flex flex-col">
                        <span class="{{ $label }}">{{ __('Resume / CV') }}</span>
                        <div class="relative flex w-full cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-[1.5px] border-dashed border-[rgba(13,13,13,0.28)] px-5 py-7 transition-[border-color,background] duration-250 hover:border-[rgba(200,16,46,0.4)] hover:bg-[rgba(200,16,46,0.025)] dark:border-white/10 dark:hover:bg-[rgba(200,16,46,0.05)]">
                            <input type="file" wire:model="resume" accept=".pdf,.doc,.docx" @change="file = $event.target.files[0]?.name ?? ''" class="absolute inset-0 size-full cursor-pointer opacity-0" aria-label="{{ __('Upload your CV') }}" />
                            <span class="grid size-10 place-items-center rounded-xl bg-[rgba(200,16,46,0.08)] text-brand" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" /><polyline points="17 8 12 3 7 8" /><line x1="12" y1="3" x2="12" y2="15" /></svg>
                            </span>
                            <span class="text-[13px] font-semibold text-ink rtl:font-arabic">{{ __('Upload your CV') }}</span>
                            <span class="text-[11px] text-ink-60 rtl:font-arabic">{{ __('PDF, DOC or DOCX · Max 5MB') }}</span>
                            <span x-show="file" x-text="file" class="max-w-full truncate text-[11px] font-semibold text-brand"></span>
                            <span wire:loading wire:target="resume" class="text-[11px] text-ink-60 rtl:font-arabic">{{ __('Uploading…') }}</span>
                        </div>
                        @error('resume') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>

                    <div class="group/field mb-[22px] flex flex-col">
                        <label for="af-note" class="{{ $label }}">{{ __('Cover Note') }} <span class="font-normal tracking-normal normal-case opacity-50">{{ __('(optional)') }}</span></label>
                        <textarea id="af-note" wire:model="note" class="{{ $field }} h-[100px] resize-none leading-[1.65]!" placeholder="{{ __('Tell us why you\'re the right fit for this role…') }}"></textarea>
                        @error('note') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>

                    <div class="mt-1 flex flex-wrap items-center justify-between gap-3">
                        <p class="max-w-[28ch] text-[11px] leading-[1.5] text-ink-60 rtl:font-arabic">{{ __('Your application will be reviewed within 5 business days.') }}</p>
                        <button type="submit" wire:loading.attr="disabled" wire:target="send,resume" class="group inline-flex cursor-pointer items-center gap-2 rounded-full border border-white/20 bg-[linear-gradient(135deg,rgba(215,20,52,0.96)_0%,rgb(185,10,36)_100%)] px-[30px] py-[14px] font-sans text-[13px] leading-none font-[650] tracking-[0.055em] whitespace-nowrap text-white backdrop-blur-[20px] transition-all duration-300 ease-out-ex hover:-translate-y-0.5 hover:bg-[linear-gradient(135deg,rgba(225,25,58,0.98)_0%,rgb(200,16,46)_100%)] disabled:opacity-60 rtl:font-arabic">
                            {{ __('Submit Application') }}
                            <svg width="14" height="10" viewBox="0 0 16 12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="rtl:-scale-x-100"><path d="M1 6h14M9 1l6 5-6 5" /></svg>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
