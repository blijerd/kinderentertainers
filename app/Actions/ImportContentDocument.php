<?php

namespace App\Actions;

use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\ContentMedia;
use App\Models\LandingPage;
use App\Support\Content\ContentFrontMatter;
use App\Support\Content\ContentMediaReferences;
use App\Support\UniqueSlug;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportContentDocument
{
    public function __construct(
        private readonly UpsertLandingPage $upsertLandingPage,
        private readonly UpsertBlogPost $upsertBlogPost,
        private readonly ImportContentMedia $importContentMedia,
        private readonly CreateBlogTag $createBlogTag,
        private readonly ContentMediaReferences $mediaReferences,
    ) {}

    public function handle(string $absolutePath, string $type, ?string $contentRoot = null): LandingPage|BlogPost
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            throw ValidationException::withMessages([
                'file' => 'Het contentbestand bestaat niet of is niet leesbaar.',
            ]);
        }

        $contents = file_get_contents($absolutePath);

        if ($contents === false) {
            throw ValidationException::withMessages([
                'file' => 'Het contentbestand kon niet worden gelezen.',
            ]);
        }

        $parsed = ContentFrontMatter::parse($contents);
        $attributes = $parsed['attributes'];
        $contentRoot ??= (string) config('content.path');
        $relativePath = $this->relativePath($absolutePath, $contentRoot);
        $slug = $this->resolveSlug($attributes['slug'] ?? null, $absolutePath);

        $body = $this->mediaReferences->rewrite($parsed['body'], function (string $filename) use ($contentRoot, $attributes): ?string {
            $media = $this->importReferencedMedia($contentRoot, 'media/'.$filename, $attributes['title'] ?? null);

            return $media?->url();
        });

        $payload = [
            'title' => $attributes['title'] ?? Str::headline($slug),
            'slug' => $slug,
            'intro' => $attributes['intro'] ?? null,
            'body' => $body !== '' ? $body : null,
            'seo_title' => $attributes['seo_title'] ?? null,
            'meta_description' => $attributes['meta_description'] ?? null,
            'canonical_url' => $this->absoluteUrl($attributes['canonical_url'] ?? null),
            'source_path' => $relativePath,
            'noindex' => (bool) ($attributes['noindex'] ?? false),
            'is_published' => (bool) ($attributes['published'] ?? $attributes['is_published'] ?? false),
            'published_at' => $attributes['published_at'] ?? null,
        ];

        if ($type === 'page') {
            $payload['cta_label'] = $attributes['cta_label'] ?? null;
            $payload['cta_url'] = $attributes['cta_url'] ?? null;
            $payload['og_image_path'] = $this->resolveStoredImagePath(
                $contentRoot,
                $attributes['og_image'] ?? $attributes['og_image_path'] ?? null,
                $attributes['title'] ?? null,
            );

            return $this->upsertLandingPage->handle(null, $payload);
        }

        if ($type === 'blog') {
            $payload['cover_image_path'] = $this->resolveStoredImagePath(
                $contentRoot,
                $attributes['cover_image'] ?? $attributes['cover_image_path'] ?? null,
                $attributes['title'] ?? null,
            );
            $payload['og_image_path'] = $this->resolveStoredImagePath(
                $contentRoot,
                $attributes['og_image'] ?? $attributes['og_image_path'] ?? null,
                $attributes['title'] ?? null,
            );
            $payload['tag_ids'] = $this->resolveTagIds($attributes['tags'] ?? $attributes['tag'] ?? null);

            return $this->upsertBlogPost->handle(null, $payload);
        }

        throw ValidationException::withMessages([
            'type' => 'Onbekend contenttype. Gebruik page of blog.',
        ]);
    }

    private function resolveSlug(mixed $slug, string $absolutePath): string
    {
        $fromAttribute = Str::slug((string) $slug);

        if ($fromAttribute !== '') {
            return $fromAttribute;
        }

        return Str::slug((string) pathinfo($absolutePath, PATHINFO_FILENAME));
    }

    private function relativePath(string $absolutePath, string $contentRoot): string
    {
        $root = rtrim($contentRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (str_starts_with($absolutePath, $root)) {
            return str_replace(DIRECTORY_SEPARATOR, '/', substr($absolutePath, strlen($root)));
        }

        return basename($absolutePath);
    }

    private function resolveStoredImagePath(string $contentRoot, mixed $value, mixed $alt): ?string
    {
        $sourcePath = $this->mediaReferences->normalizeSourcePath(is_string($value) ? $value : null);

        if ($sourcePath === null) {
            return is_string($value) && $value !== '' ? $value : null;
        }

        return $this->importReferencedMedia($contentRoot, $sourcePath, $alt)?->path;
    }

    private function importReferencedMedia(string $contentRoot, string $sourcePath, mixed $alt): ?ContentMedia
    {
        $absolutePath = rtrim($contentRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $sourcePath);

        if (! is_file($absolutePath)) {
            return null;
        }

        return $this->importContentMedia->handle($absolutePath, [
            'source_path' => $sourcePath,
            'alt_text' => is_string($alt) ? $alt : null,
        ]);
    }

    /**
     * @return list<int>
     */
    private function resolveTagIds(mixed $tags): array
    {
        $names = [];

        if (is_string($tags) && $tags !== '') {
            $names = preg_split('/\s*,\s*/', $tags) ?: [];
        } elseif (is_array($tags)) {
            $names = $tags;
        }

        $ids = [];

        foreach ($names as $name) {
            if (! is_string($name) && ! is_int($name)) {
                continue;
            }

            $name = trim((string) $name);

            if ($name === '') {
                continue;
            }

            $slug = UniqueSlug::normalize($name);
            $tag = BlogTag::withTrashed()
                ->where(function ($query) use ($slug, $name): void {
                    $query->where('slug', $slug)->orWhere('name', $name);
                })
                ->first();

            if ($tag !== null) {
                if ($tag->trashed()) {
                    $tag->restore();
                }

                $ids[] = $tag->id;

                continue;
            }

            $ids[] = $this->createBlogTag->handle(['name' => $name])->id;
        }

        return array_values(array_unique($ids));
    }

    private function absoluteUrl(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_URL) ? $value : null;
    }
}
