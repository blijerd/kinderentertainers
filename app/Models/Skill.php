<?php

namespace App\Models;

use Database\Factories\SkillFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'description', 'icon', 'active'])]
class Skill extends Model
{
    /** @use HasFactory<SkillFactory> */
    use HasFactory;

    public function entertainers(): BelongsToMany
    {
        return $this->belongsToMany(Entertainer::class)->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
}
