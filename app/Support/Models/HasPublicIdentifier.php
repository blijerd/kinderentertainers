<?php

namespace App\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasPublicIdentifier
{
    public static function bootHasPublicIdentifier(): void
    {
        static::creating(function (Model $model): void {
            $column = $model->publicIdentifierColumn();

            if (filled($model->getAttribute($column))) {
                return;
            }

            $model->forceFill([
                $column => $model->generateUniquePublicIdentifier(),
            ]);
        });
    }

    protected function publicIdentifierColumn(): string
    {
        return 'public_id';
    }

    public function getRouteKeyName(): string
    {
        return $this->publicIdentifierColumn();
    }

    protected function generateUniquePublicIdentifier(): string
    {
        $column = $this->publicIdentifierColumn();

        do {
            $identifier = (string) Str::uuid();
        } while (static::query()->where($column, $identifier)->exists());

        return $identifier;
    }
}
