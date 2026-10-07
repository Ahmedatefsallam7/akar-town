<?php

namespace App\Filament\Helpers;

use App\Jobs\SendLeadToCrm;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The same CRM pieces on every lead screen (contact messages, meetings, sell requests).
 */
class CrmHelper
{
    /**
     * "Send to CRM" on a lead's View page. Sending happens by itself (and unsent leads are retried every hour); this is
     * for sending one at once, e.g. right after the CRM settings are fixed.
     *
     * @param  class-string<resource>  $resource
     */
    public static function sendAction(string $resource): Action
    {
        return Action::make('sendToCrm')
            ->label(__('admin.contact_us.send_to_crm'))
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->visible(fn (Model $record) => ! $record->isSentToCrm() && $resource::canEdit($record))
            ->action(function (Model $record) {
                SendLeadToCrm::dispatch($record);

                Notification::make()->success()->title(__('admin.contact_us.notifications.queued_for_crm'))->send();
            });
    }

    public static function sentColumn(): IconColumn
    {
        return IconColumn::make('crm_lead_id')
            ->label(__('admin.contact_us.crm'))
            ->state(fn (Model $record) => $record->isSentToCrm())
            ->boolean()
            ->alignment('center')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * Sent / not sent to the CRM, to spot leads the CRM didn't get.
     */
    public static function sentFilter(): TernaryFilter
    {
        return TernaryFilter::make('sent_to_crm')
            ->label(__('admin.contact_us.crm'))
            ->indicator(__('admin.contact_us.crm'))
            ->queries(
                true: fn (Builder $query) => $query->whereNotNull('crm_lead_id'),
                false: fn (Builder $query) => $query->whereNull('crm_lead_id'),
                blank: fn (Builder $query) => $query,
            );
    }
}
