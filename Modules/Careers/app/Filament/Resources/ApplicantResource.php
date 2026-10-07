<?php

namespace Modules\Careers\Filament\Resources;

use App\Filament\Contracts\HasPermissionModule;
use App\Filament\Helpers\TableHelper;
use App\Filament\Support\PrefixSearch;
use App\Filament\Traits\HasResourceTranslations;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Modules\Careers\Filament\Resources\ApplicantResource\Pages;
use Modules\Careers\Models\Applicant;
use Modules\Careers\Models\Career;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * "Applicants": job applications from the careers page, for HR to screen.
 */
class ApplicantResource extends Resource implements HasPermissionModule
{
    use HasResourceTranslations;

    /** Searched by how they start (see PrefixSearch): fast with many applications. */
    public const SEARCH_COLUMNS = ['name', 'email', 'phone'];

    protected static ?string $permissionGroup = 'inquiries_crm';

    protected static ?string $model = Applicant::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.inquiries_crm');
    }

    public static function getModelLabel(): string
    {
        return __('admin.applicants.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.applicants.plural');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return static::SEARCH_COLUMNS;
    }

    protected static function applyGlobalSearchAttributeConstraints(Builder $query, string $search): void
    {
        PrefixSearch::apply($query, $search, static::SEARCH_COLUMNS);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.tabs.applicant_details'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.applicants.name'))
                            ->required()
                            ->maxLength(191),
                        TextInput::make('email')
                            ->label(__('admin.applicants.email'))
                            ->email()
                            ->required()
                            ->maxLength(191),
                        TextInput::make('phone')
                            ->label(__('admin.applicants.phone'))
                            ->tel()
                            ->required()
                            ->maxLength(30),
                        Select::make('career_id')
                            ->label(__('admin.applicants.career'))
                            // Jobs in the trash aren't offered, but an application keeps the one it was made for.
                            ->relationship('career', getLocalizedFieldName('title'), fn (Builder $query) => $query->withoutTrashed())
                            ->getOptionLabelFromRecordUsing(fn (Career $record) => $record->getLocalized('title'))
                            ->searchable(['title_en', 'title_ar'])
                            ->preload()
                            ->required(),
                        Select::make('status')
                            ->label(__('admin.applicants.status'))
                            ->options(Applicant::statusOptions())
                            ->default(Applicant::PENDING)
                            ->required()
                            ->native(false),
                        Select::make('gender')
                            ->label(__('admin.applicants.gender'))
                            ->options(Applicant::genderOptions())
                            ->native(false),
                        TextInput::make('linkedin_profile')
                            ->label(__('admin.applicants.linkedin_profile'))
                            ->url()
                            ->rules(['url:http,https'])
                            ->maxLength(500)
                            ->columnSpanFull(),
                        SpatieMediaLibraryFileUpload::make('resume')
                            ->label(__('admin.applicants.resume'))
                            ->helperText(__('admin.applicants.resume_help'))
                            ->collection(Applicant::RESUME)
                            // Private disk: a CV never gets a public link.
                            ->disk(Applicant::RESUME_DISK)
                            ->visibility('private')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ])
                            ->maxSize(5120)
                            ->columnSpanFull(),
                        Textarea::make('cover_letter')
                            ->label(__('admin.applicants.cover_letter'))
                            ->rows(5)
                            ->maxLength(10000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableHelper::emptyState($table, static::class, 'heroicon-o-user-group')
            ->striped()
            ->splitSearchTerms(false)
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'career:id,title_en,title_ar,deleted_at',
                'media' => fn ($query) => $query->where('collection_name', Applicant::RESUME),
            ]))
            ->columns([
                TableHelper::indexColumn(),
                TextColumn::make('name')
                    ->label(__('admin.applicants.name'))
                    // One search box for name, email and phone, by how they start.
                    ->searchable(query: PrefixSearch::using(static::SEARCH_COLUMNS))
                    ->sortable()
                    ->weight('medium')
                    ->url(fn (Applicant $record) => static::getUrl('view', ['record' => $record]))
                    ->color('primary'),
                TextColumn::make('email')
                    ->label(__('admin.applicants.email')),
                TextColumn::make('career_title')
                    ->label(__('admin.applicants.career'))
                    ->state(fn (Applicant $record) => $record->career?->getLocalized('title'))
                    ->wrap(),
                TextColumn::make('status')
                    ->label(__('admin.applicants.status'))
                    ->formatStateUsing(fn (string $state) => Applicant::statusOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state) => Applicant::statusColor($state))
                    ->sortable()
                    ->alignment('center'),
                TextColumn::make('phone')
                    ->label(__('admin.applicants.phone'))
                    ->extraAttributes(['dir' => 'ltr'])
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('gender')
                    ->label(__('admin.applicants.gender'))
                    ->formatStateUsing(fn (?string $state) => $state ? (Applicant::genderOptions()[$state] ?? $state) : null)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('linkedin_profile')
                    ->label(__('admin.applicants.linkedin_profile'))
                    ->url(fn (Applicant $record) => $record->linkedin_profile, shouldOpenInNewTab: true)
                    ->limit(30)
                    ->color('primary')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TableHelper::createdAtColumn(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.applicants.status'))
                    ->options(Applicant::statusOptions())
                    ->multiple(),
                SelectFilter::make('career_id')
                    ->label(__('admin.applicants.career'))
                    ->relationship('career', getLocalizedFieldName('title'))
                    ->searchable()
                    ->preload(),
                QueryBuilder::make()
                    ->constraints([
                        TextConstraint::make('name')->label(__('admin.applicants.name')),
                        TextConstraint::make('email')->label(__('admin.applicants.email')),
                        TextConstraint::make('phone')->label(__('admin.applicants.phone')),
                        SelectConstraint::make('gender')->label(__('admin.applicants.gender'))->options(Applicant::genderOptions()),
                    ]),
                TableHelper::dateRangeFilter('created_at', 'created_from', 'created_until'),
                TrashedFilter::make(),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->recordActions([
                static::downloadResumeAction()->iconButton()->tooltip(__('admin.applicants.download_resume')),
                ...TableHelper::recordActions(
                    fn (Applicant $record) => __('admin.applicants.notifications.deleted', ['name' => $record->name]),
                ),
            ])
            ->recordActionsColumnLabel(__('admin.table.actions'))
            ->recordActionsAlignment('center')
            ->toolbarActions(TableHelper::standardBulkActions([
                static::markAsBulkAction(Applicant::REVIEWED, 'heroicon-o-eye'),
                static::markAsBulkAction(Applicant::ACCEPTED, 'heroicon-o-check-circle'),
                static::markAsBulkAction(Applicant::REJECTED, 'heroicon-o-x-circle'),
            ]));
    }

    /**
     * Downloads the CV from the private disk, for users who can see applicants.
     */
    public static function downloadResumeAction(): Action
    {
        return Action::make('downloadResume')
            ->label(__('admin.applicants.download_resume'))
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->visible(fn (Applicant $record) => $record->getFirstMedia(Applicant::RESUME) !== null && static::canView($record))
            ->action(fn (Applicant $record): Media => $record->getFirstMedia(Applicant::RESUME));
    }

    /**
     * Sets the status of the selected applications that can move to it (accepted / rejected ones are left as is).
     * Needs edit + bulk; applications you can't edit are skipped.
     */
    protected static function markAsBulkAction(string $status, string $icon): BulkAction
    {
        return BulkAction::make("mark_{$status}")
            ->label(__("admin.applicants.mark_{$status}"))
            ->icon($icon)
            ->requiresConfirmation()
            ->authorize(fn (): bool => static::can('updateAny'))
            ->action(fn (Collection $records) => $records
                ->filter(fn (Applicant $record) => in_array($record->status, Applicant::statusesBefore($status), true) && static::canEdit($record))
                ->each->update(['status' => $status]))
            ->successNotificationTitle(__('admin.notifications.updated'))
            ->deselectRecordsAfterCompletion();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplicants::route('/'),
            'create' => Pages\CreateApplicant::route('/create'),
            'view' => Pages\ViewApplicant::route('/{record}'),
            'edit' => Pages\EditApplicant::route('/{record}/edit'),
        ];
    }
}
