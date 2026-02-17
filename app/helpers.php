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
 * Normalize UK mobile number to E.164 (+447XXXXXXXXX).
 * Accepts: 07..., +447..., +4407..., 447..., 00447..., with or without spaces/dashes.
 *
 * @return string|null +447XXXXXXXXX or null if invalid
 */
function normalizeUkWhatsAppPhone(?string $input): ?string
{
    if (! is_string($input) || trim($input) === '') {
        return null;
    }

    $digits = preg_replace('/\D/', '', $input);
    if ($digits === '') {
        return null;
    }

    // Strip international prefix 00
    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);
    }

    // Strip leading 0 (UK national format: 07...)
    while (str_starts_with($digits, '0')) {
        $digits = substr($digits, 1);
    }

    // Strip country code 44
    if (str_starts_with($digits, '44')) {
        $digits = substr($digits, 2);
    }

    // Strip leading 0 after 44 (e.g. +4407...)
    if (str_starts_with($digits, '0')) {
        $digits = substr($digits, 1);
    }

    // UK mobile: 7 followed by 9 digits (10 digits total)
    if (strlen($digits) !== 10 || $digits[0] !== '7') {
        return null;
    }

    return '+44'.$digits;
}

/**
 * Mask phone number for display (e.g. +44***123).
 */
function maskPhone(string $phone): string
{
    $len = strlen($phone);
    if ($len <= 6) {
        return str_repeat('*', $len);
    }

    return substr($phone, 0, 3).str_repeat('*', min($len - 6, 6)).substr($phone, -3);
}

/**
 * Get an SEO setting value with fallback.
 */
function seo(string $key, ?string $default = null): ?string
{
    return \App\Models\SeoSetting::cached($key) ?? $default;
}
