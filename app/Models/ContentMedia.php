<?php

namespace App\Models;

use App\Support\Models\HasPublicIdentifier;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'original_filename',
    'path',
    'disk',
    'mime_type',
    'byte_size',
    'checksum',
    'alt_text',
    'source_path',
])]
class ContentMedia extends Model
{
    use HasFactory, HasPublicIdentifier, SoftDeletes;

    public function url(): string
    {
        return Storage::disk($this->disk ?: 'public')->url($this->path);
    }

    protected function casts(): array
    {
        return [
            'byte_size' => 'integer',
        ];
    }
}
