<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __(config('app.name')) }}</title>
    @vite('resources/css/app.css')
</head>
<body class="flex min-h-screen items-center justify-center bg-gray-50 p-6 text-center text-gray-900">
    <main class="max-w-md">
        <h1 class="text-2xl font-bold">{{ __(config('app.name')) }}</h1>
        <p class="mt-3 text-gray-600">{{ __('admin.site_status.offline_message') }}</p>
    </main>
</body>
</html>
