<?php

namespace App\Support\Content;

final class ContentFrontMatter
{
    /**
     * @return array{attributes: array<string, mixed>, body: string}
     */
    public static function parse(string $contents): array
    {
        $contents = preg_replace("/^\u{FEFF}/", '', $contents) ?? $contents;

        if (! preg_match('/\A---\r?\n(.*?)\r?\n---\r?\n?(.*)\z/s', $contents, $matches)) {
            return [
                'attributes' => [],
                'body' => trim($contents),
            ];
        }

        $attributes = [];

        foreach (preg_split('/\r?\n/', (string) $matches[1]) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (! preg_match('/^([A-Za-z0-9_]+):\s*(.*)$/', $line, $pair)) {
                continue;
            }

            $attributes[$pair[1]] = self::castValue($pair[2]);
        }

        return [
            'attributes' => $attributes,
            'body' => trim((string) $matches[2]),
        ];
    }

    private static function castValue(string $value): mixed
    {
        $value = trim($value);

        if ($value === '' || strcasecmp($value, 'null') === 0 || $value === '~') {
            return null;
        }

        if (preg_match('/^(["\'])(.*)\1$/', $value, $quoted)) {
            return $quoted[2];
        }

        return match (strtolower($value)) {
            'true', 'yes', 'on' => true,
            'false', 'no', 'off' => false,
            default => is_numeric($value) && ! str_starts_with($value, '0') && ! str_contains($value, ' ')
                ? (str_contains($value, '.') ? (float) $value : (int) $value)
                : $value,
        };
    }
}
