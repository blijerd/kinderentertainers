<?php

namespace App\Models;

use App\Support\Models\HasPublicIdentifier;
use Database\Factories\BlogPostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

#[Fillable([
    'author_id',
    'title',
    'slug',
    'intro',
    'body',
    'cover_image_path',
    'og_image_path',
    'seo_title',
    'meta_description',
    'canonical_url',
    'noindex',
    'is_published',
    'published_at',
    'source_path',
])]
class BlogPost extends Model
{
    /** @use HasFactory<BlogPostFactory> */
    use HasFactory, HasPublicIdentifier, SoftDeletes;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag')->withTimestamps();
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

    public function scopeLatestPublished(Builder $query): Builder
    {
        return $query
            ->orderByRaw('coalesce('.$query->getModel()->getTable().'.published_at, '.$query->getModel()->getTable().'.created_at) desc')
            ->orderByDesc($query->getModel()->getTable().'.id');
    }

    public function scopeIndexable(Builder $query): Builder
    {
        return $query->published()->where('noindex', false);
    }

    public function seoTitle(): string
    {
        return $this->seo_title ?: $this->title;
    }

    public function canonicalUrl(): string
    {
        return $this->canonical_url ?: route('blog.show', $this);
    }

    public function coverImageUrl(): ?string
    {
        return $this->cover_image_path ? Storage::disk('public')->url($this->cover_image_path) : null;
    }

    public function ogImageUrl(): ?string
    {
        $path = $this->og_image_path ?: $this->cover_image_path;

        return $path ? Storage::disk('public')->url($path) : null;
    }

    public function metaDescriptionText(): string
    {
        if (filled($this->meta_description)) {
            return (string) $this->meta_description;
        }

        if (filled($this->intro)) {
            return Str::limit((string) $this->intro, 160);
        }

        $plain = trim(html_entity_decode(strip_tags((string) $this->bodyHtml()), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return Str::limit($plain, 160);
    }

    public function bodyHtml(): HtmlString
    {
        return new HtmlString(Str::markdown((string) $this->body, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]));
    }

    /**
     * @return Collection<int, BlogPost>
     */
    public function relatedPublishedPosts(int $limit = 3): Collection
    {
        $tagIds = $this->tags->modelKeys();

        $query = static::query()
            ->published()
            ->with('tags')
            ->whereKeyNot($this->getKey())
            ->latestPublished();

        if ($tagIds !== []) {
            $query->whereHas(
                'tags',
                fn (Builder $tags): Builder => $tags->whereIn('blog_tags.id', $tagIds),
            );
        }

        return $query->limit($limit)->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonLd(): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $this->title,
            'description' => $this->metaDescriptionText(),
            'url' => $this->canonicalUrl(),
            'mainEntityOfPage' => $this->canonicalUrl(),
            'datePublished' => ($this->published_at ?? $this->created_at)?->toAtomString(),
            'dateModified' => $this->updated_at?->toAtomString(),
            'inLanguage' => 'nl-NL',
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('company.legal_name'),
                'url' => url('/'),
            ],
        ];

        if ($this->ogImageUrl()) {
            $schema['image'] = [$this->ogImageUrl()];
        }

        if ($this->author?->name) {
            $schema['author'] = [
                '@type' => 'Person',
                'name' => $this->author->name,
            ];
        }

        $keywords = $this->tags->pluck('name')->filter()->implode(', ');

        if ($keywords !== '') {
            $schema['keywords'] = $keywords;
        }

        return $schema;
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
                    'name' => $this->title,
                    'item' => $this->canonicalUrl(),
                ],
            ],
        ];
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
