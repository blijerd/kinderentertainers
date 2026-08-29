<?php

if (! function_exists('kinderentertainers_view_cache_generation')) {
    function kinderentertainers_view_cache_generation(): string
    {
        $candidates = [
            trim((string) ($_ENV['KE_BUILD_REF'] ?? $_SERVER['KE_BUILD_REF'] ?? '')),
            trim((string) getenv('KE_BUILD_REF')),
        ];

        $buildRefPath = dirname(__DIR__).'/bootstrap/build-ref';

        if (is_file($buildRefPath) && is_readable($buildRefPath)) {
            $candidates[] = trim((string) file_get_contents($buildRefPath));
        }

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && preg_match('/^[A-Za-z0-9._-]{4,16}$/', $candidate) === 1) {
                return $candidate;
            }
        }

        return 'local';
    }
}

if (! function_exists('kinderentertainers_compiled_views_path')) {
    function kinderentertainers_compiled_views_path(): string
    {
        $override = trim((string) ($_ENV['VIEW_COMPILED_PATH'] ?? $_SERVER['VIEW_COMPILED_PATH'] ?? getenv('VIEW_COMPILED_PATH') ?: ''));

        if ($override !== '') {
            return $override;
        }

        $storage = function_exists('storage_path')
            ? storage_path('framework/views')
            : dirname(__DIR__).'/storage/framework/views';

        return $storage.DIRECTORY_SEPARATOR.kinderentertainers_view_cache_generation();
    }
}
