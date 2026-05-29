<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

#[Fillable([
    'title',
    'slug',
    'intro',
    'body',
    'cta_label',
    'cta_url',
    'seo_title',
    'meta_description',
    'canonical_url',
    'og_image_path',
    'noindex',
    'is_published',
    'published_at',
])]
class LandingPage extends Model
{
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function seoTitle(): string
    {
        return $this->seo_title ?: $this->title;
    }

    public function canonicalUrl(): string
    {
        return $this->canonical_url ?: route('landing-pages.show', $this);
    }

    public function ogImageUrl(): ?string
    {
        return $this->og_image_path ? Storage::disk('public')->url($this->og_image_path) : null;
    }

    public function safeCtaUrl(): ?string
    {
        $url = trim((string) $this->cta_url);

        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        return filter_var($url, FILTER_VALIDATE_URL) && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)
            ? $url
            : null;
    }

    public function bodyHtml(): HtmlString
    {
        return new HtmlString(Str::markdown((string) $this->body, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]));
    }

    protected function casts(): array
    {
        return [
            'noindex' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
