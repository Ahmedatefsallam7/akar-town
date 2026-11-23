<?php

namespace App\Providers;

use App\Support\Images\ProtectedImages;
use DateTimeInterface;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Mattiverse\Userstamps\Userstamps;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\ResponsiveImages\Jobs\GenerateResponsiveImagesJob;

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

        // Making an image's sizes opens the whole image: a 360° camera's photo (12000 × 6000) needs hundreds of MB,
        // more than PHP's usual 128 MB. Only these queue jobs get more; the website's requests keep the usual limit.
        Queue::before(function (JobProcessing $event): void {
            if (in_array($event->job->resolveName(), [PerformConversionsJob::class, GenerateResponsiveImagesJob::class], true)) {
                ini_set('memory_limit', '1G');
            }
        });

        // Every admin upload is saved under a random name with the extension of what the file really is (jpg, png,
        // pdf…), never the one the uploader typed: a "photo" named a.html could otherwise be opened as a web page
        // on this site and run a script. "Important" so it applies after each field's own setup (media fields too).
        FileUpload::configureUsing(fn (FileUpload $field) => $field->getUploadedFileNameForStorageUsing(
            fn (TemporaryUploadedFile $file) => Str::ulid().'.'.($file->guessExtension() ?? 'bin'),
        ), isImportant: true);

        // Protected images (AKAR-51): a file on the private "media" disk shown by the admin's upload fields gets the same
        // short-lived signed link as on the website.
        Storage::disk(ProtectedImages::DISK)->buildTemporaryUrlsUsing(
            fn (string $path, DateTimeInterface $expiration) => ProtectedImages::url($path, max(60, $expiration->getTimestamp() - time())),
        );

        // The website declares both fonts on every page (English pages show "عر"), but only Arabic pages preload the
        // Arabic one: an English page downloads it only when it needs those few letters.
        Vite::usePreloadTagAttributes(fn (?string $src, ?string $url) => $src === 'fonts' && str_contains((string) $url, 'noto-kufi-arabic') && app()->getLocale() !== 'ar' ? false : []);

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
