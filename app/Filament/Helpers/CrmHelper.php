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
use Throwable;

/**
 * The same CRM pieces on every lead screen (contact messages, meetings, sell requests).
 */
class CrmHelper
{
    /**
     * "Send to CRM" on a lead's View page. Sending happens by itself (and unsent leads are retried every hour); this
     * sends one at once, e.g. right after the CRM settings are fixed, and says why when the CRM didn't take it.
     *
     * @param  class-string<resource>  $resource
     */
    public static function sendAction(string $resource): Action
    {
        return Action::make('sendToCrm')
            ->label(__('admin.contact_us.send_to_crm'))
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->visible(fn (Model $record) => ! $record->isSentToCrm()
                && ! (method_exists($record, 'trashed') && $record->trashed())
                && $resource::canEdit($record))
            ->action(function (Model $record) {
                $notSent = fn (string $reason) => Notification::make()->danger()->persistent()
                    ->title(__('admin.crm.not_sent'))->body($reason)->send();

                if (! SendLeadToCrm::isConfigured()) {
                    $notSent(__('admin.crm.errors.settings'));

                    return;
                }

                try {
                    SendLeadToCrm::dispatchSync($record);
                } catch (Throwable $exception) {
                    $notSent(SendLeadToCrm::failureReason($exception));

                    return;
                }

                Notification::make()->success()->title(__('admin.crm.sent', ['id' => $record->refresh()->crm_lead_id]))->send();
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
