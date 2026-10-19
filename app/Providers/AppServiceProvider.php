<?php

namespace App\Providers;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Mattiverse\Userstamps\Userstamps;
use Spatie\MediaLibrary\HasMedia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // created_by / updated_by / deleted_by point at admin users: take them from the admin login only. A logged-in
        // website user (the "website" guard) leaves them empty; their leads record who they are in website_user_id.
        Userstamps::resolveUsing(fn () => Auth::guard('web')->id());

        // Every admin upload is saved under a random name with the extension of what the file really is (jpg, png,
        // pdf…), never the one the uploader typed: a "photo" named a.html could otherwise be opened as a web page
        // on this site and run a script. "Important" so it applies after each field's own setup (media fields too).
        FileUpload::configureUsing(fn (FileUpload $field) => $field->getUploadedFileNameForStorageUsing(
            fn (TemporaryUploadedFile $file) => Str::ulid().'.'.($file->guessExtension() ?? 'bin'),
        ), isImportant: true);

        // Filament's media upload field reloads the record's media for every field (one query each). Read them once
        // per record instead: the first field loads them, the others reuse them (same files, same order).
        SpatieMediaLibraryFileUpload::configureUsing(fn (SpatieMediaLibraryFileUpload $field) => $field
            ->loadStateFromRelationshipsUsing(static function (SpatieMediaLibraryFileUpload $component, HasMedia $record): void {
                /** @var Model&HasMedia $record */
                $record->loadMissing('media');

                $media = $record->getMedia($component->getCollection() ?? 'default')
                    ->when($component->hasMediaFilter(), fn ($media) => $component->filterMedia($media))
                    ->when(! $component->isMultiple(), fn ($media) => $media->take(1))
                    ->mapWithKeys(fn ($media): array => [$media->uuid => $media->uuid])
                    ->toArray();

                $component->rawState($media);
            }));
    }
}
