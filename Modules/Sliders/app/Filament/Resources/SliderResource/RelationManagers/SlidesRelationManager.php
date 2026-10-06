<?php

namespace Modules\Sliders\Filament\Resources\SliderResource\RelationManagers;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\Unique;
use Modules\Projects\Filament\Resources\ProjectResource;
use Modules\Projects\Models\Project;
use Modules\Sliders\Filament\Resources\SliderResource;
use Modules\Sliders\Filament\Resources\SliderResource\Pages\ViewSlider;
use Modules\Sliders\Models\SliderItem;

/**
 * The hero slider's slides (as in Dastan): each one is a project, shown with its title, description and image.
 * Managing slides needs permission to edit the slider.
 */
class SlidesRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.sliders.slides');
    }

    /**
     * Only the hero slider has slides; the body blocks list their content by themselves.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $pageClass === ViewSlider::class && $ownerRecord->isHero();
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function getAuthorizationResponse(string $action, ?Model $record = null): Response
    {
        return SliderResource::getAuthorizationResponse(in_array($action, ['view', 'viewAny'], true) ? 'view' : 'update', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('project_id')
                    ->label(__('admin.sliders.project'))
                    ->relationship(
                        'project',
                        getLocalizedFieldName('title'),
                        fn (Builder $query) => $query->withoutTrashed()->with('developer'),
                    )
                    ->getOptionLabelFromRecordUsing(fn (Project $record) => collect([$record->getLocalized('title'), $record->developer?->getLocalized('name')])->filter()->join(' - '))
                    ->searchable(['title_en', 'title_ar'])
                    ->preload()
                    ->required()
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule->where('slider_id', $this->getOwnerRecord()->getKey()))
                    ->validationMessages(['unique' => __('admin.sliders.project_taken')])
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label(__('admin.sliders.active'))
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'project.developer',
                'project.media' => fn ($query) => $query->where('collection_name', Project::FEATURED_IMAGE),
            ]))
            ->columns([
                ImageColumn::make('image')
                    ->label(__('admin.table.image'))
                    ->state(fn (SliderItem $record) => $record->project?->getFirstMediaUrl(Project::FEATURED_IMAGE, 'thumb') ?: null)
                    ->imageSize(40)
                    ->square(),
                TextColumn::make('project_title')
                    ->label(__('admin.sliders.project'))
                    ->state(fn (SliderItem $record) => $record->project?->getLocalized('title'))
                    ->description(fn (SliderItem $record) => $record->project?->developer?->getLocalized('name'))
                    ->url(fn (SliderItem $record) => $record->project && ProjectResource::canView($record->project) ? ProjectResource::getUrl('view', ['record' => $record->project]) : null)
                    ->weight('medium')
                    ->color('primary'),
                TextColumn::make('order')
                    ->label(__('admin.sliders.order'))
                    ->alignment('center'),
                IconColumn::make('is_active')
                    ->label(__('admin.sliders.active'))
                    ->boolean()
                    ->alignment('center'),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->paginated(false)
            ->emptyStateHeading(__('admin.sliders.no_slides'))
            ->emptyStateDescription(__('admin.sliders.no_slides_help'))
            ->headerActions([
                CreateAction::make()
                    ->label(__('admin.actions.create_title', ['name' => __('admin.sliders.slide')]))
                    ->icon('heroicon-o-plus')
                    // A new slide goes last.
                    ->mutateDataUsing(fn (array $data) => [...$data, 'order' => (int) $this->getOwnerRecord()->items()->max('order') + 1]),
            ])
            ->recordActions([
                EditAction::make()->label(__('admin.actions.edit'))->iconButton()->tooltip(__('admin.actions.edit')),
                DeleteAction::make()->label(__('admin.actions.delete'))->iconButton()->tooltip(__('admin.actions.delete')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    $this->statusBulkAction('activate', 'heroicon-o-check-circle', true),
                    $this->statusBulkAction('deactivate', 'heroicon-o-x-circle', false),
                    DeleteBulkAction::make()->label(__('admin.actions.delete')),
                ]),
            ]);
    }

    private function statusBulkAction(string $name, string $icon, bool $value): BulkAction
    {
        return BulkAction::make($name)
            ->label(__("admin.actions.{$name}"))
            ->icon($icon)
            ->requiresConfirmation()
            ->authorize(fn (): bool => $this->getAuthorizationResponse('update')->allowed())
            ->action(fn (Collection $records) => SliderItem::query()->whereKey($records->modelKeys())->update(['is_active' => $value]))
            ->successNotificationTitle(__('admin.notifications.updated'))
            ->deselectRecordsAfterCompletion();
    }
}
