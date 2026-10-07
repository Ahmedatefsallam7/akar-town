<?php

namespace Modules\Projects\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Projects\Filament\Resources\ProjectResource\Pages\ViewProject;
use Modules\Sliders\Filament\Resources\SliderResource;
use Modules\Sliders\Models\Slider;
use Modules\Sliders\Models\SliderItem;

/**
 * The project's homepage slides, on its View page: add it to the hero slider (last) or take it off.
 * Slides are part of the slider, so changing them needs permission to edit sliders.
 */
class SlidersRelationManager extends RelationManager
{
    protected static string $relationship = 'sliderItems';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.sliders.plural');
    }

    /**
     * Only on the View page, and only for users who can see sliders.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $pageClass === ViewProject::class && SliderResource::canViewAny();
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function getAuthorizationResponse(string $action, ?Model $record = null): Response
    {
        return SliderResource::getAuthorizationResponse(in_array($action, ['view', 'viewAny'], true) ? 'view' : 'update');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('slider'))
            ->columns([
                TextColumn::make('slider_title')
                    ->label(__('admin.table.title'))
                    ->state(fn (SliderItem $record) => $record->slider->getLocalized('title'))
                    ->url(fn (SliderItem $record) => SliderResource::getUrl('view', ['record' => $record->slider]))
                    ->weight('medium')
                    ->color('primary'),
                TextColumn::make('placement')
                    ->label(__('admin.sliders.placement'))
                    ->state(fn (SliderItem $record) => Slider::placementOptions()[$record->slider->placement] ?? $record->slider->placement)
                    ->badge()
                    ->color('gray'),
                TextColumn::make('order')
                    ->label(__('admin.sliders.order'))
                    ->alignment('center'),
                IconColumn::make('is_active')
                    ->label(__('admin.sliders.active'))
                    ->boolean()
                    ->alignment('center'),
            ])
            ->emptyStateHeading(__('admin.sliders.none_for_project'))
            ->headerActions([
                Action::make('addToHero')
                    ->label(__('admin.sliders.add_to_hero'))
                    ->icon('heroicon-o-plus')
                    ->authorize(fn (): bool => $this->getAuthorizationResponse('create')->allowed())
                    ->visible(fn (): bool => ! $this->getOwnerRecord()->trashed()
                        && ($hero = $this->heroSlider()) !== null
                        && ! $hero->items()->where('project_id', $this->getOwnerRecord()->getKey())->exists())
                    ->action(function (): void {
                        $hero = $this->heroSlider();
                        $hero->items()->create([
                            'project_id' => $this->getOwnerRecord()->getKey(),
                            'order' => (int) $hero->items()->max('order') + 1,
                        ]);
                    })
                    ->successNotificationTitle(__('admin.notifications.updated')),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label(__('admin.sliders.remove_slide'))
                    ->modalHeading(__('admin.sliders.remove_slide'))
                    ->iconButton()
                    ->tooltip(__('admin.sliders.remove_slide')),
            ]);
    }

    private function heroSlider(): ?Slider
    {
        return once(fn () => Slider::query()->where('placement', Slider::HOME_HERO)->orderBy('order')->first());
    }
}
