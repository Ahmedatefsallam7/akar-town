<?php

namespace Modules\Inquiry\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Modules\Inquiry\Filament\Resources\MeetingResource;
use Modules\Inquiry\Models\Meeting;

/**
 * Dashboard calendar of Meetings & Viewings on their meeting time (month / week / day, today, back / forward). The
 * calendar asks for the dates on screen only, so it stays quick with many bookings.
 */
class MeetingsCalendarWidget extends Widget
{
    /** Status colours, like the list badges. */
    private const COLORS = [
        Meeting::PENDING => '#f59e0b',
        Meeting::CONFIRMED => '#10b981',
        Meeting::RESCHEDULED => '#06b6d4',
        Meeting::COMPLETED => '#6366f1',
        Meeting::CANCELLED => '#ef4444',
    ];

    /** A day can't hold more than this on screen anyway; protects the page from a huge range. */
    private const MAX_EVENTS = 1000;

    protected string $view = 'inquiry::filament.widgets.meetings-calendar';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return MeetingResource::canViewAny();
    }

    /**
     * Bookings between two dates (what the calendar shows), as calendar events.
     *
     * @return array<array<string, mixed>>
     */
    public function events(string $start, string $end): array
    {
        abort_unless(static::canView(), 403);

        // The calendar sends local dates ("2026-10-01T00:00:00", any offset ignored): read them as app (Cairo) time.
        try {
            $from = Carbon::parse(substr($start, 0, 19));
            $until = Carbon::parse(substr($end, 0, 19));
        } catch (\Throwable) {
            return [];
        }

        if ($until->lte($from) || $from->diffInDays($until) > 62) {
            return [];
        }

        return Meeting::query()
            ->whereBetween('meeting_at', [$from, $until])
            ->orderBy('meeting_at')
            ->limit(self::MAX_EVENTS)
            ->get(['id', 'name', 'type', 'status', 'meeting_at'])
            ->map(fn (Meeting $meeting) => [
                'id' => $meeting->id,
                'title' => $meeting->name.' · '.(Meeting::typeOptions()[$meeting->type] ?? $meeting->type),
                // No offset: shown at the time it was booked (Cairo), whatever the browser's time zone.
                'start' => $meeting->meeting_at->format('Y-m-d\TH:i:s'),
                'url' => MeetingResource::getUrl('view', ['record' => $meeting]),
                'color' => self::COLORS[$meeting->status] ?? '#64748b',
            ])
            ->all();
    }
}
