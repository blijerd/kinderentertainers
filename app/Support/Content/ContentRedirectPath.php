<?php

namespace App\Support\Content;

final class ContentRedirectPath
{
    public static function normalizeFrom(mixed $value): string
    {
        $path = self::extractPath((string) $value);

        if ($path === '' || $path === '/') {
            return '/';
        }

        return '/'.strtolower(trim($path, '/'));
    }

    public static function normalizeTo(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if (self::isAbsoluteUrl($value)) {
            return $value;
        }

        return self::normalizeFrom($value);
    }

    public static function isAbsoluteUrl(string $value): bool
    {
        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }

        return in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true);
    }

    public static function isReservedFrom(string $fromPath): bool
    {
        $slug = ltrim($fromPath, '/');

        return $fromPath === '/' || ReservedPublicSlugs::isReserved($slug);
    }

    public static function destinationPath(string $to): string
    {
        if (self::isAbsoluteUrl($to)) {
            $path = parse_url($to, PHP_URL_PATH);

            return is_string($path) && $path !== '' ? self::normalizeFrom($path) : '/';
        }

        return self::normalizeFrom($to);
    }

    public static function withIncomingQuery(string $to, string $query): string
    {
        if ($query === '' || str_contains($to, '?')) {
            return $to;
        }

        return $to.'?'.$query;
    }

    private static function extractPath(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (self::isAbsoluteUrl($value)) {
            $path = parse_url($value, PHP_URL_PATH);

            return is_string($path) ? $path : '/';
        }

        $withoutQuery = explode('?', $value, 2)[0];

        return explode('#', $withoutQuery, 2)[0];
    }
}
