<?php

if (! function_exists('getLocalizedFieldName')) {
    /**
     * Get the column name for the current locale, e.g. "name" => "name_ar".
     */
    function getLocalizedFieldName(string $field): string
    {
        return app()->getLocale() === 'ar' ? "{$field}_ar" : "{$field}_en";
    }
}
