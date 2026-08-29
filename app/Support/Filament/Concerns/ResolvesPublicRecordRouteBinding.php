<?php

namespace App\Support\Filament\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait ResolvesPublicRecordRouteBinding
{
    public static function resolveRecordRouteBinding(string|int $key, ?Closure $modifyQuery = null): ?Model
    {
        if (! is_string($key) || ! Str::isUuid($key)) {
            return null;
        }

        return parent::resolveRecordRouteBinding($key, $modifyQuery);
    }
}
