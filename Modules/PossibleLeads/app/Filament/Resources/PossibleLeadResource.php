<?php

namespace Modules\PossibleLeads\Filament\Resources;

use App\Filament\Contracts\HasPermissionModule;
use App\Filament\Helpers\TableHelper;
use App\Filament\Traits\HasResourceTranslations;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inquiry\Filament\Resources\InquiryResource;
use Modules\Inquiry\Filament\Resources\MeetingResource;
use Modules\Inquiry\Models\Inquiry;
use Modules\Inquiry\Models\Meeting;
use Modules\PossibleLeads\Filament\Resources\PossibleLeadResource\Pages;
use Modules\PossibleLeads\Models\PossibleLead;
use Modules\Projects\Filament\Resources\ProjectResource;
use Modules\UnitRequests\Filament\Resources\UnitRequestResource;
use Modules\UnitRequests\Models\UnitRequest;
use Modules\Units\Filament\Resources\UnitResource;
use Modules\Users\Enums\PermissionAction;

/**
 * "Possible Leads": interest clicks on the website (form, WhatsApp, phone) for sales to follow up, even before a
 * visitor sends a form. Recorded by the website; here they are read, filtered and cleaned up only.
 */
class PossibleLeadResource extends Resource implements HasPermissionModule
{
    use HasResourceTranslations;

    protected static ?string $permissionGroup = 'inquiries_crm';

    /** @var array<PermissionAction> */
    protected static array $permissionActions = [PermissionAction::View, PermissionAction::Delete, PermissionAction::Bulk];

    protected static ?string $model = PossibleLead::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cursor-arrow-rays';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.inquiries_crm');
    }

    public static function getModelLabel(): string
    {
        return __('admin.possible_leads.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.possible_leads.plural');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'unit:id,name_en,name_ar,deleted_at',
                'project:id,title_en,title_ar,deleted_at',
                'websiteUser:id,name,deleted_at',
            ]))
            ->emptyStateIcon('heroicon-o-cursor-arrow-rays')
            ->emptyStateHeading(__('admin.possible_leads.empty'))
            ->emptyStateDescription(__('admin.possible_leads.empty_help'))
            ->columns([
                TableHelper::indexColumn(),
                TextColumn::make('click_type')
                    ->label(__('admin.possible_leads.click_type'))
                    ->formatStateUsing(fn (string $state) => PossibleLead::typeOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        PossibleLead::WHATSAPP => 'success',
                        PossibleLead::PHONE => 'info',
                        default => 'primary',
                    })
                    ->icon(fn (string $state) => match ($state) {
                        PossibleLead::WHATSAPP => 'heroicon-m-chat-bubble-left-right',
                        PossibleLead::PHONE => 'heroicon-m-phone',
                        default => 'heroicon-m-document-text',
                    }),
                TextColumn::make('page_url')
                    ->label(__('admin.possible_leads.page_url'))
                    ->url(fn (PossibleLead $record) => $record->page_url, shouldOpenInNewTab: true)
                    // Only the path: the site's address is the same on every row.
                    ->formatStateUsing(fn (?string $state) => $state ? (parse_url($state, PHP_URL_PATH) ?: '/') : null)
                    ->limit(30)
                    ->extraAttributes(['dir' => 'ltr'])
                    ->tooltip(fn (PossibleLead $record) => $record->page_url)
                    ->color('primary')
                    ->placeholder('—'),
                TextColumn::make('listing')
                    ->label(__('admin.possible_leads.listing'))
                    ->state(fn (PossibleLead $record) => $record->unit?->getLocalized('name') ?? $record->project?->getLocalized('title'))
                    ->description(fn (PossibleLead $record) => match (true) {
                        $record->unit !== null => __('admin.units.singular'),
                        $record->project !== null => __('admin.projects.singular'),
                        default => null,
                    })
                    ->url(fn (PossibleLead $record) => static::listingUrl($record))
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('visitor')
                    ->label(__('admin.possible_leads.visitor'))
                    ->state(fn (PossibleLead $record) => $record->websiteUser?->name ?? __('admin.possible_leads.guest', ['id' => substr($record->visitor_id, 0, 8)]))
                    ->color(fn (PossibleLead $record) => $record->websiteUser ? 'primary' : 'gray')
                    ->toggleable(),
                IconColumn::make('converted')
                    ->label(__('admin.possible_leads.converted'))
                    ->state(fn (PossibleLead $record) => $record->isConverted())
                    ->boolean()
                    ->url(fn (PossibleLead $record) => static::convertedLeadUrl($record))
                    ->alignment('center'),
                TextColumn::make('created_at')
                    ->label(__('admin.possible_leads.clicked_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100])
            ->filters([
                TableHelper::dateRangeFilter('created_at', 'created_from', 'created_until'),
                SelectFilter::make('click_type')
                    ->label(__('admin.possible_leads.click_type'))
                    ->options(PossibleLead::typeOptions())
                    ->multiple(),
                TernaryFilter::make('converted')
                    ->label(__('admin.possible_leads.converted'))
                    ->indicator(__('admin.possible_leads.converted'))
                    ->queries(
                        true: fn (Builder $query) => $query->converted(),
                        false: fn (Builder $query) => $query->converted(false),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->recordActions([
                DeleteAction::make()->label(__('admin.actions.delete'))->iconButton()->tooltip(__('admin.actions.delete')),
            ])
            ->recordActionsColumnLabel(__('admin.table.actions'))
            ->recordActionsAlignment('center')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('admin.actions.delete')),
                ]),
            ]);
    }

    private static function listingUrl(PossibleLead $record): ?string
    {
        return match (true) {
            $record->unit !== null && UnitResource::canView($record->unit) => UnitResource::getUrl('view', ['record' => $record->unit]),
            $record->project !== null && ProjectResource::canView($record->project) => ProjectResource::getUrl('view', ['record' => $record->project]),
            default => null,
        };
    }

    /**
     * The lead it became (contact message, meeting or sell request), for users who can see it.
     */
    private static function convertedLeadUrl(PossibleLead $record): ?string
    {
        $resource = match ($record->converted_lead_type) {
            (new Inquiry)->getMorphClass() => InquiryResource::class,
            (new Meeting)->getMorphClass() => MeetingResource::class,
            (new UnitRequest)->getMorphClass() => UnitRequestResource::class,
            default => null,
        };

        return $resource && $record->converted_lead_id && $resource::canViewAny()
            ? $resource::getUrl('view', ['record' => $record->converted_lead_id])
            : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPossibleLeads::route('/'),
        ];
    }
}
