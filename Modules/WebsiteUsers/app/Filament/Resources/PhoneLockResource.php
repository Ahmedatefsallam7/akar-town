<?php

namespace Modules\WebsiteUsers\Filament\Resources;

use App\Filament\Concerns\HasResourceTranslations;
use App\Filament\Contracts\HasPermissionModule;
use App\Filament\Support\NavigationBadges;
use App\Filament\Support\PrefixSearch;
use App\Filament\Support\TableHelper;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Users\Enums\PermissionAction;
use Modules\WebsiteUsers\Filament\Resources\PhoneLockResource\Pages;
use Modules\WebsiteUsers\Models\PhoneLock;
use Modules\WebsiteUsers\Support\PhoneLogin;

/**
 * "WhatsApp Locks" (AKAR-49): numbers locked for WhatsApp sign-in after 3 wrong codes, with Unlock; every lock is
 * kept (who unlocked it and when), so the list is also the log. A number can be locked before it has an account.
 */
class PhoneLockResource extends Resource implements HasPermissionModule
{
    use HasResourceTranslations;

    protected static ?string $permissionGroup = 'inquiries_crm';

    /** @var array<PermissionAction> */
    protected static array $permissionActions = [PermissionAction::View, PermissionAction::Edit];

    protected static ?string $model = PhoneLock::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.inquiries_crm');
    }

    public static function getModelLabel(): string
    {
        return __('admin.phone_locks.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.phone_locks.plural');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * The sidebar number: numbers locked now (not the log).
     */
    public static function getNavigationBadgeCount(): int
    {
        return PhoneLock::query()->active()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['websiteUser:id,name', 'unlockedBy:id,first_name,last_name']))
            ->emptyStateIcon('heroicon-o-lock-open')
            ->emptyStateHeading(__('admin.phone_locks.empty'))
            ->columns([
                TableHelper::indexColumn(),
                TextColumn::make('phone')
                    ->label(__('admin.phone_locks.phone'))
                    ->searchable(query: PrefixSearch::using(['phone']))
                    ->copyable()
                    ->weight('medium')
                    ->extraAttributes(['dir' => 'ltr']),
                TextColumn::make('websiteUser.name')
                    ->label(__('admin.phone_locks.account'))
                    ->placeholder(__('admin.phone_locks.no_account'))
                    ->url(fn (PhoneLock $record) => $record->website_user_id && WebsiteUserResource::canViewAny()
                        ? WebsiteUserResource::getUrl('view', ['record' => $record->website_user_id]) : null)
                    ->color('primary'),
                TextColumn::make('status')
                    ->label(__('admin.phone_locks.status'))
                    ->state(fn (PhoneLock $record) => $record->isActive() ? 'locked' : 'unlocked')
                    ->formatStateUsing(fn (string $state) => __("admin.phone_locks.{$state}"))
                    ->badge()
                    ->color(fn (string $state) => $state === 'locked' ? 'danger' : 'success')
                    ->alignment('center'),
                TextColumn::make('attempts')
                    ->label(__('admin.phone_locks.attempts'))
                    ->alignment('center'),
                TextColumn::make('created_at')
                    ->label(__('admin.phone_locks.locked_at'))
                    ->dateTime()
                    ->sortable()
                    ->description(fn (PhoneLock $record) => $record->created_at?->diffForHumans()),
                TextColumn::make('ip')
                    ->label(__('admin.phone_locks.ip'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('unlocked_at')
                    ->label(__('admin.phone_locks.unlocked'))
                    ->dateTime()
                    ->placeholder('—')
                    ->description(fn (PhoneLock $record) => $record->unlocked_at
                        ? __('admin.phone_locks.unlocked_by', ['name' => $record->unlockedBy?->name ?? '—']) : null),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100])
            ->filters([
                TableHelper::dateRangeFilter('created_at', 'locked_from', 'locked_until'),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->recordActions([static::unlockAction()])
            ->recordActionsColumnLabel(__('admin.table.actions'))
            ->recordActionsAlignment('center');
    }

    /**
     * Unlocks the number: 3 fresh tries, and the visitor can ask for a code again.
     */
    public static function unlockAction(): Action
    {
        return Action::make('unlock')
            ->label(__('admin.phone_locks.unlock'))
            ->icon('heroicon-o-lock-open')
            ->color('success')
            ->button()
            ->requiresConfirmation()
            ->modalDescription(fn (PhoneLock $record) => __('admin.phone_locks.unlock_confirm', ['phone' => $record->phone]))
            ->visible(fn (PhoneLock $record) => $record->isActive() && static::canEdit($record))
            ->action(function (PhoneLock $record) {
                PhoneLogin::unlock($record, auth()->user());
                NavigationBadges::forget();
            })
            ->successNotificationTitle(__('admin.phone_locks.unlocked_notice'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPhoneLocks::route('/'),
        ];
    }
}
