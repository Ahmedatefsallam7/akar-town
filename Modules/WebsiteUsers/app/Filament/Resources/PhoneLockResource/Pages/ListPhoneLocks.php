<?php

namespace Modules\WebsiteUsers\Filament\Resources\PhoneLockResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Modules\WebsiteUsers\Filament\Resources\PhoneLockResource;
use Modules\WebsiteUsers\Models\PhoneLock;

/**
 * The numbers locked now first (with Unlock), and every lock so far (the log) on the second tab.
 */
class ListPhoneLocks extends ListRecords
{
    protected static string $resource = PhoneLockResource::class;

    public function getTitle(): string
    {
        return __('admin.phone_locks.plural');
    }

    public function getSubheading(): ?string
    {
        return __('admin.phone_locks.subheading');
    }

    public function getTabs(): array
    {
        return [
            'locked' => Tab::make(__('admin.phone_locks.tab_locked'))
                ->icon('heroicon-o-lock-closed')
                ->badge(PhoneLock::query()->active()->count() ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('unlocked_at')),
            'all' => Tab::make(__('admin.phone_locks.tab_all'))
                ->icon('heroicon-o-clock'),
        ];
    }
}
