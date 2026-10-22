<?php

declare(strict_types=1);

$requestedFile = $_GET['file'] ?? '';

if (! is_string($requestedFile) || ! str_starts_with($requestedFile, 'public/')) {
    http_response_code(404);

    exit;
}

$publicRoot = realpath(__DIR__.'/../public');
$filePath = realpath(__DIR__.'/../'.$requestedFile);

if ($publicRoot === false || $filePath === false || ! is_file($filePath)) {
    http_response_code(404);

    exit;
}

$isInsidePublic = str_starts_with($filePath, $publicRoot.DIRECTORY_SEPARATOR);
$extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$mimeTypes = [
    'css' => 'text/css; charset=utf-8',
    'eot' => 'application/vnd.ms-fontobject',
    'gif' => 'image/gif',
    'ico' => 'image/x-icon',
    'jpeg' => 'image/jpeg',
    'jpg' => 'image/jpeg',
    'js' => 'application/javascript; charset=utf-8',
    'json' => 'application/json; charset=utf-8',
    'map' => 'application/json; charset=utf-8',
    'png' => 'image/png',
    'svg' => 'image/svg+xml',
    'ttf' => 'font/ttf',
    'webp' => 'image/webp',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
];

if (! $isInsidePublic || ! isset($mimeTypes[$extension])) {
    http_response_code(404);

    exit;
}

header('Content-Type: '.$mimeTypes[$extension]);
header('Content-Length: '.(string) filesize($filePath));
header('X-Content-Type-Options: nosniff');

if (str_starts_with($requestedFile, 'public/build/assets/')) {
    header('Cache-Control: public, max-age=31536000, immutable');
} else {
    header('Cache-Control: public, max-age=3600');
}

readfile($filePath);
