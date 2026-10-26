{{--
    The signed-in visitor's account (not in the design; user asked): their details, password and what they sent from the
    website (App\Support\Website\AccountRequests), in tabs, plus sign out. Opened from the account menu.
--}}
@php
    $user = auth('website')->user();
    $requests = \App\Support\Website\AccountRequests::for($user);
    $tabs = ['details' => __('My details'), 'password' => __('Password'), 'requests' => __('My requests'), 'favorites' => __('Wishlist')];
    $pill = 'cursor-pointer rounded-full border px-5 py-2 text-[12.5px] font-semibold transition-all duration-200 rtl:font-arabic';
@endphp

<x-website.layout :title="__('My Account')">
    <x-website.page-hero
        :crumb="__('My Account')"
        :title="__('Hello, :name', ['name' => '<em>'.e(\Illuminate\Support\Str::of($user->name)->before(' ')).'</em>'])"
        :count="count($requests)"
        :count-label="trans_choice('Request|Requests', count($requests))"
        :animate="false"
    />

    {{-- The open tab follows the address (#password, #requests) so it can be linked and survives a reload. --}}
    <section
        x-data="{ tab: ['details', 'password', 'requests', 'favorites'].includes(location.hash.slice(1)) ? location.hash.slice(1) : 'details' }"
        x-init="$watch('tab', (value) => history.replaceState(null, '', '#' + value))"
        class="bg-page pt-12 pb-[100px] max-md:pt-8 max-md:pb-16 dark:bg-[#100f0d]"
    >
        <div class="mx-auto max-w-[920px] px-12 max-md:px-5">
            <div class="mb-7 flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap gap-2" role="tablist">
                    @foreach ($tabs as $key => $name)
                        <button
                            type="button"
                            role="tab"
                            :aria-selected="tab === @js($key)"
                            @click="tab = @js($key)"
                            class="{{ $pill }}"
                            :class="tab === @js($key) ? 'border-brand bg-brand text-white' : 'border-ink-10 bg-white text-ink-60 hover:border-[rgba(200,16,46,0.3)] hover:text-ink dark:border-white/10 dark:bg-[#1a1916] dark:text-white/55'"
                        >{{ $name }}</button>
                    @endforeach
                </div>
                <form method="POST" action="{{ route('account.logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex cursor-pointer items-center gap-2 text-[13px] font-semibold text-ink-60 transition-colors duration-200 hover:text-brand rtl:font-arabic">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="rtl:-scale-x-100" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" /><polyline points="16 17 21 12 16 7" /><line x1="21" y1="12" x2="9" y2="12" /></svg>
                        {{ __('Sign out') }}
                    </button>
                </form>
            </div>

            <div class="rounded-2xl border border-ink-10 bg-white p-9 shadow-[0_8px_32px_rgba(13,13,13,0.05)] max-md:p-6 dark:border-white/8 dark:bg-[#1a1916]">
                <livewire:website.account-settings />

                {{-- My requests --}}
                <div x-show="tab === 'requests'" x-cloak>
                    @forelse ($requests as $request)
                        <div class="flex items-start justify-between gap-4 border-b border-ink-10 py-4 first:pt-0 last:border-b-0 last:pb-0 dark:border-white/8">
                            <div class="min-w-0">
                                <p class="mb-1 text-[10px] font-bold tracking-[0.18em] text-brand uppercase rtl:font-arabic rtl:tracking-normal">{{ $request['kind'] }}</p>
                                <p class="text-[15px] font-semibold text-ink rtl:font-arabic">{{ $request['title'] }}</p>
                                @if ($request['detail'])
                                    <p class="mt-0.5 truncate text-[13px] text-ink-60 rtl:font-arabic">{{ $request['detail'] }}</p>
                                @endif
                                <p class="mt-1 text-[12px] text-ink-30 rtl:font-arabic dark:text-white/35">{{ __('Sent :date', ['date' => $request['date']->locale(app()->getLocale())->translatedFormat('j F Y')]) }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-3 py-1 text-[11px] font-semibold rtl:font-arabic {{ $request['tone'] }}">{{ $request['status'] }}</span>
                        </div>
                    @empty
                        <div class="py-10 text-center">
                            <p class="mb-5 text-[14px] text-ink-60 rtl:font-arabic">{{ __('You haven’t sent anything yet. Book a viewing, send a message or submit your property and follow it here.') }}</p>
                            <x-website.button :href="route('properties')" arrow>{{ __('Explore Properties') }}</x-website.button>
                        </div>
                    @endforelse
                </div>

                <div x-show="tab === 'favorites'" x-cloak>
                    <livewire:website.wishlist :compact="true" />
                </div>
            </div>
        </div>
    </section>
</x-website.layout>
