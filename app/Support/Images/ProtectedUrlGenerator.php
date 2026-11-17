<?php

namespace App\Support\Images;

use DateTimeInterface;
use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;

/**
 * Media library links (AKAR-51): a file on the protected "media" disk gets a short-lived signed link (ProtectedImages),
 * in the admin and on the website alike; files on other disks (e.g. the applicants' CVs) keep their usual links.
 */
class ProtectedUrlGenerator extends DefaultUrlGenerator
{
    public function getUrl(): string
    {
        return $this->isProtected() ? ProtectedImages::url($this->getPathRelativeToRoot()) : parent::getUrl();
    }

    public function getTemporaryUrl(DateTimeInterface $expiration, array $options = []): string
    {
        return $this->isProtected()
            ? ProtectedImages::url($this->getPathRelativeToRoot(), max(60, $expiration->getTimestamp() - time()))
            : parent::getTemporaryUrl($expiration, $options);
    }

    private function isProtected(): bool
    {
        return ($this->conversion ? $this->media->conversions_disk : $this->media->disk) === ProtectedImages::DISK;
    }
}
