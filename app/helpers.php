<?php

declare(strict_types=1);

/**
 * Get localized value with fallback. Amharic is default; English falls back to Amharic.
 *
 * @param  object  $model  Eloquent model with _en and _am attributes
 * @param  string  $baseAttr  Base attribute name (e.g. 'day_title' → day_title_en, day_title_am)
 * @param  string|null  $locale  Locale ('en' or 'am'); defaults to app locale
 */
function localized(object $model, string $baseAttr, ?string $locale = null): ?string
{
    $locale = $locale ?? app()->getLocale();
    $locale = in_array($locale, ['en', 'am'], true) ? $locale : 'en';

    $enAttr = $baseAttr.'_en';
    $amAttr = $baseAttr.'_am';

    $val = $locale === 'en' ? ($model->{$enAttr} ?? $model->{$amAttr}) : ($model->{$amAttr} ?? $model->{$enAttr});

    return is_string($val) && $val !== '' ? $val : null;
}

/**
 * Get an SEO setting value with fallback.
 */
function seo(string $key, ?string $default = null): ?string
{
    return \App\Models\SeoSetting::cached($key) ?? $default;
}
