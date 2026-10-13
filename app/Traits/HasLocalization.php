<?php

namespace App\Traits;

trait HasLocalization
{
    /**
     * @var array<string, array<string>> Per-class cache of localized fields.
     */
    private static array $localizedFieldsCache = [];

    /**
     * Auto-discover localized fields by scanning fillable attributes for _en/_ar suffixes.
     * Override in your model to explicitly declare localized fields.
     *
     * @return array<string> e.g. ['name', 'title', 'description']
     */
    public function localizedFields(): array
    {
        $class = static::class;

        if (isset(static::$localizedFieldsCache[$class])) {
            return static::$localizedFieldsCache[$class];
        }

        $fields = [];
        foreach ($this->getFillable() as $field) {
            if (str_ends_with($field, '_en') || str_ends_with($field, '_ar')) {
                $base = preg_replace('/_(en|ar)$/', '', $field);
                $fields[$base] = $base;
            }
        }

        return static::$localizedFieldsCache[$class] = array_values($fields);
    }

    /**
     * Get a localized attribute value: current locale, then fallback locale, then any variant.
     */
    public function getLocalized(string $attribute): ?string
    {
        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale', 'en');

        if (! empty($this->{"{$attribute}_{$locale}"})) {
            return $this->{"{$attribute}_{$locale}"};
        }

        if ($locale !== $fallback && ! empty($this->{"{$attribute}_{$fallback}"})) {
            return $this->{"{$attribute}_{$fallback}"};
        }

        return $this->{"{$attribute}_en"} ?: ($this->{"{$attribute}_ar"} ?: null);
    }

    /**
     * Get all locale variants for a given attribute.
     *
     * @return array<string, string|null> e.g. ['en' => 'Hello', 'ar' => 'مرحبا']
     */
    public function getLocalizedVariants(string $attribute): array
    {
        $variants = [];

        foreach (array_keys(config('app.available_locales')) as $locale) {
            $variants[$locale] = $this->{"{$attribute}_{$locale}"} ?? null;
        }

        return $variants;
    }

    /**
     * Allow $model->name to return the localized value directly.
     */
    public function __get($key)
    {
        if (in_array($key, $this->localizedFields(), true)) {
            return $this->getLocalized($key);
        }

        return parent::__get($key);
    }

    public function __isset($key)
    {
        if (in_array($key, $this->localizedFields(), true)) {
            return $this->getLocalized($key) !== null;
        }

        return parent::__isset($key);
    }
}
