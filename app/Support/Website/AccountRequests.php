<?php

namespace App\Support\Website;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Inquiry\Models\Inquiry;
use Modules\Inquiry\Models\Meeting;
use Modules\UnitRequests\Models\UnitRequest;
use Modules\WebsiteUsers\Models\WebsiteUser;

/**
 * What a signed-in visitor sent from the website (the account page's "My requests"), newest first: viewing bookings,
 * sell requests and messages, with their status (messages have none: "Received"). Three small queries, each capped.
 */
class AccountRequests
{
    /** The most recent ones of each kind. */
    private const LIMIT = 30;

    /** Admin status colour → the site's badge look. */
    private const TONES = [
        'warning' => 'bg-[rgba(217,119,6,0.1)] text-[#b45309]',
        'info' => 'bg-[rgba(37,99,235,0.08)] text-[#1d4ed8]',
        'primary' => 'bg-[rgba(200,16,46,0.08)] text-brand',
        'success' => 'bg-[rgba(25,160,70,0.1)] text-[#15803d]',
        'danger' => 'bg-[rgba(220,38,38,0.08)] text-[#b91c1c]',
        'gray' => 'bg-ink-10 text-ink-60',
    ];

    /**
     * @return list<array{kind: string, title: string, detail: ?string, status: string, tone: string, date: Carbon}>
     */
    public static function for(WebsiteUser $user): array
    {
        $locale = app()->getLocale();

        $meetings = Meeting::query()->where('website_user_id', $user->id)->latest()->limit(self::LIMIT)
            ->get(['type', 'status', 'meeting_at', 'source', 'created_at'])
            ->map(fn (Meeting $meeting) => [
                'kind' => __('Viewing'),
                // "Property page: <name>" → the property's name.
                'title' => Str::after((string) $meeting->source, ': ') ?: __('Viewing'),
                'detail' => Meeting::typeOptions()[$meeting->type].' · '.$meeting->meeting_at->locale($locale)->translatedFormat('j F Y, g:i A'),
                'status' => Meeting::statusOptions()[$meeting->status] ?? $meeting->status,
                'tone' => self::TONES[Meeting::statusColor($meeting->status)] ?? self::TONES['gray'],
                'date' => $meeting->created_at,
            ]);

        $sellRequests = UnitRequest::query()->where('website_user_id', $user->id)->latest()->limit(self::LIMIT)
            ->get(['name_en', 'name_ar', 'price', 'status', 'created_at'])
            ->map(fn (UnitRequest $request) => [
                'kind' => __('Sell request'),
                'title' => $request->{"name_{$locale}"} ?: $request->name_en,
                'detail' => $request->price ? number_format($request->price).' '.__('EGP') : null,
                'status' => UnitRequest::statusOptions()[$request->status] ?? $request->status,
                'tone' => self::TONES[UnitRequest::statusColor($request->status)] ?? self::TONES['gray'],
                'date' => $request->created_at,
            ]);

        $messages = Inquiry::query()->where('website_user_id', $user->id)->latest()->limit(self::LIMIT)
            ->get(['form', 'message', 'created_at'])
            ->map(fn (Inquiry $inquiry) => [
                'kind' => __('Message'),
                'title' => Inquiry::formOptions()[$inquiry->crmForm()],
                // The first line says what it was about ("Project: Azure Heights", "Interested in: …").
                'detail' => Str::limit(strtok((string) $inquiry->message, "\n") ?: '', 90) ?: null,
                'status' => __('Received'),
                'tone' => self::TONES['gray'],
                'date' => $inquiry->created_at,
            ]);

        return $meetings->concat($sellRequests)->concat($messages)
            ->sortByDesc('date')
            ->values()
            ->all();
    }
}
