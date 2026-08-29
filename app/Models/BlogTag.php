<?php

namespace App\Models;

use App\Support\Models\HasPublicIdentifier;
use Database\Factories\BlogTagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'slug',
    'description',
    'seo_title',
    'meta_description',
    'noindex',
])]
class BlogTag extends Model
{
    /** @use HasFactory<BlogTagFactory> */
    use HasFactory, HasPublicIdentifier, SoftDeletes;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tag')->withTimestamps();
    }

    public function scopeWithPublishedPosts(Builder $query): Builder
    {
        return $query->whereHas('posts', fn (Builder $posts): Builder => $posts->published());
    }

    public function seoTitle(): string
    {
        return $this->seo_title ?: $this->name.' — blog';
    }

    public function canonicalUrl(): string
    {
        return route('blog.tag', $this);
    }

    public function metaDescriptionText(): string
    {
        if (filled($this->meta_description)) {
            return (string) $this->meta_description;
        }

        if (filled($this->description)) {
            return Str::limit((string) $this->description, 160);
        }

        return 'Artikelen over '.$this->name.' op Kinderentertainers.nl.';
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonLd(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $this->seoTitle(),
            'description' => $this->metaDescriptionText(),
            'url' => $this->canonicalUrl(),
            'isPartOf' => [
                '@type' => 'Blog',
                'name' => 'Blog — Kinderentertainers.nl',
                'url' => route('blog.index'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function breadcrumbJsonLd(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => route('home'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Blog',
                    'item' => route('blog.index'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $this->name,
                    'item' => $this->canonicalUrl(),
                ],
            ],
        ];
    }

    protected function casts(): array
    {
        return [
            'noindex' => 'boolean',
        ];
    }
}
