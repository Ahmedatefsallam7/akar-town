<?php

namespace App\Console\Commands;

use App\Support\Images\ProtectedImages;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Moves the images uploaded before AKAR-51 to the private "media" disk (out of /storage), then makes their website
 * sizes again with the watermark. Run once after the update; running it again does nothing more.
 */
class ProtectImages extends Command
{
    protected $signature = 'images:protect {--files-only : Move the files without making the sizes again}';

    protected $description = 'Move the uploaded images to the private disk and watermark their website sizes (AKAR-51)';

    /** The site's own images (settings, team photos), moved whole. */
    private const FOLDERS = ['settings/images', 'settings/icons', 'avatars'];

    public function handle(): int
    {
        $from = Storage::disk('public');
        $to = Storage::disk(ProtectedImages::DISK);
        $moved = [];

        // Media library files: each in its own folder named after its id.
        Media::query()->where('disk', 'public')->chunkById(200, function ($media) use ($from, $to, &$moved) {
            foreach ($media as $item) {
                $folder = (string) $item->getKey();
                if (File::isDirectory($from->path($folder))) {
                    File::ensureDirectoryExists(dirname($to->path($folder)));
                    File::moveDirectory($from->path($folder), $to->path($folder), overwrite: true);
                }
                $item->forceFill(['disk' => ProtectedImages::DISK, 'conversions_disk' => ProtectedImages::DISK])->saveQuietly();
                $moved[] = $item->getKey();
            }
        });
        $this->info(count($moved).' uploaded images moved.');

        foreach (self::FOLDERS as $folder) {
            if (File::isDirectory($from->path($folder))) {
                File::ensureDirectoryExists(dirname($to->path($folder)));
                File::copyDirectory($from->path($folder), $to->path($folder));
                File::deleteDirectory($from->path($folder));
                $this->info("{$folder} moved.");
            }
        }

        if ($moved && ! $this->option('files-only')) {
            $this->info('Making the website sizes again, with the watermark…');
            $this->call('media-library:regenerate', ['--ids' => $moved, '--force' => true]);
        }

        return self::SUCCESS;
    }
}
