<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UniqueSlug
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public static function from(string $modelClass, string $source, ?Model $ignore = null, string $fallback = 'item'): string
    {
        $base = Str::slug($source);

        if ($base === '') {
            $base = $fallback;
        }

        $candidate = $base;
        $suffix = 2;

        while (static::exists($modelClass, $candidate, $ignore)) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    public static function normalize(?string $slug): string
    {
        return Str::slug((string) $slug);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected static function exists(string $modelClass, string $slug, ?Model $ignore = null): bool
    {
        $query = $modelClass::query()->withTrashed()->where('slug', $slug);

        if ($ignore !== null) {
            $query->whereKeyNot($ignore->getKey());
        }

        return $query->exists();
    }
}
