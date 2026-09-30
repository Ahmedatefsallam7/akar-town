<?php

namespace App\Filament\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait HasResourceTranslations
{
    public static function getNavigationLabel(): string
    {
        return __(static::getTranslationKey('navigation'));
    }

    public static function getBreadcrumb(): string
    {
        return __(static::getTranslationKey('navigation'));
    }

    public static function getModelLabel(): string
    {
        return __(static::getTranslationKey('model_label'));
    }

    public static function getPluralModelLabel(): string
    {
        return __(static::getTranslationKey('navigation'));
    }

    public static function getNavigationBadge(): ?string
    {
        $model = static::getModel();

        return (string) Cache::remember(
            'badge_count_'.Str::snake(class_basename($model)),
            120,
            fn () => $model::query()->count()
        );
    }

    protected static function getTranslationKey(string $type): string
    {
        $key = static::$transKey ?? Str::of(class_basename(static::class))
            ->replace('Resource', '')
            ->snake()
            ->plural()
            ->toString();

        if ($type === 'model_label') {
            return "admin.{$key}.title";
        }

        return "admin.navigation.{$key}";
    }
}
