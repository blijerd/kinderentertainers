<?php

namespace App\Support\Content;

final class ContentMediaReferences
{
    /**
     * @param  callable(string): ?string  $resolveUrl
     */
    public function rewrite(string $body, callable $resolveUrl): string
    {
        return (string) preg_replace_callback(
            '/\]\((?:\.\.\/)?(?:content\/)?media\/([^)\s]+)\)/',
            function (array $matches) use ($resolveUrl): string {
                $url = $resolveUrl($matches[1]);

                return is_string($url) && $url !== '' ? ']('.$url.')' : $matches[0];
            },
            $body,
        );
    }

    public function normalizeSourcePath(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $normalized = preg_replace('#^(\.\./)*(content/)?#', '', $value) ?? $value;

        if (! str_starts_with($normalized, 'media/')) {
            $normalized = 'media/'.ltrim($normalized, '/');
        }

        return $normalized;
    }
}
