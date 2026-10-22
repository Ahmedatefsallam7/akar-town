<?php

declare(strict_types=1);

$temporaryStoragePath = '/tmp/storage';

foreach ([
    $temporaryStoragePath.'/app/private',
    $temporaryStoragePath.'/app/public',
    $temporaryStoragePath.'/framework/cache/data',
    $temporaryStoragePath.'/framework/sessions',
    $temporaryStoragePath.'/framework/views',
    $temporaryStoragePath.'/logs',
    '/tmp/views',
    '/tmp/ssr',
] as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
}

$requestPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$publicPath = realpath(__DIR__.'/public');
$staticRoots = array_filter([
    $publicPath,
    realpath($temporaryStoragePath.'/app/public'),
]);

if ($publicPath !== false && $requestPath !== '/' && ! str_contains($requestPath, "\0")) {
    $relativePath = ltrim($requestPath, '/');
    $candidatePaths = [__DIR__.'/public/'.$relativePath];

    if (str_starts_with($relativePath, 'storage/')) {
        $candidatePaths[] = $temporaryStoragePath.'/app/public/'.substr($relativePath, strlen('storage/'));
    }

    foreach ($candidatePaths as $candidatePath) {
        $filePath = realpath($candidatePath);

        if ($filePath === false || ! is_file($filePath) || strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'php') {
            continue;
        }

        $isInsideStaticRoot = false;

        foreach ($staticRoots as $staticRoot) {
            if (str_starts_with($filePath, $staticRoot.DIRECTORY_SEPARATOR)) {
                $isInsideStaticRoot = true;
                break;
            }
        }

        if (! $isInsideStaticRoot) {
            continue;
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($filePath) ?: 'application/octet-stream';

        header('Content-Type: '.$mimeType);
        header('Content-Length: '.(string) filesize($filePath));

        if (str_starts_with($relativePath, 'build/assets/')) {
            header('Cache-Control: public, max-age=31536000, immutable');
        }

        readfile($filePath);

        exit;
    }
}

require __DIR__.'/public/index.php';
