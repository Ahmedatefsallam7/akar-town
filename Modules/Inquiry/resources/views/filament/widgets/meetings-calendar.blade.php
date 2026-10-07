{{--
    Meetings & Viewings calendar (FullCalendar from resources/js/filament/meetings-calendar.js, inside Alpine).
    The widget loads after the page, and a <script> arriving that way doesn't run, so Alpine imports the module.
--}}

<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-calendar-days" :heading="__('admin.meetings.title')">
        <div
            wire:ignore
            x-data="{
                calendar: null,
                init() {
                    window.MeetingsCalendar ? this.start() : import(@js(\Illuminate\Support\Facades\Vite::asset('resources/js/filament/meetings-calendar.js'))).then(() => this.start())
                },
                start() {
                    const kit = window.MeetingsCalendar
                    this.calendar = new kit.Calendar(this.$refs.calendar, {
                        plugins: kit.plugins,
                        locales: kit.locales,
                        locale: @js(app()->getLocale()),
                        direction: @js(__('filament-panels::layout.direction')),
                        initialView: 'dayGridMonth',
                        headerToolbar: { start: 'prev,next today', center: 'title', end: 'dayGridMonth,timeGridWeek,timeGridDay' },
                        height: 'auto',
                        dayMaxEvents: 3,
                        nowIndicator: true,
                        events: (info, success, failure) => $wire.events(info.startStr, info.endStr).then(success).catch(failure),
                    })
                    this.calendar.render()
                },
            }"
        >
            <div x-ref="calendar" class="text-sm"></div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
