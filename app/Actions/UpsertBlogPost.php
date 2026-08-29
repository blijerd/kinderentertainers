<?php

namespace App\Actions;

use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Support\UniqueSlug;

class UpsertBlogPost
{
    public function __construct(
        private readonly CreateBlogPost $createBlogPost,
        private readonly UpdateBlogPost $updateBlogPost,
        private readonly CreateBlogTag $createBlogTag,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(?BlogPost $blogPost, array $data): BlogPost
    {
        if (array_key_exists('tags', $data) && ! array_key_exists('tag_ids', $data)) {
            $data['tag_ids'] = $this->tagIdsFromNames($data['tags']);
            unset($data['tags']);
        }

        $slug = UniqueSlug::normalize((string) ($data['slug'] ?? $blogPost?->slug ?? ''));

        if ($blogPost === null && $slug !== '') {
            $blogPost = BlogPost::withTrashed()->where('slug', $slug)->first();
        }

        if ($blogPost !== null && $blogPost->exists && $blogPost->trashed()) {
            $blogPost->restore();
        }

        if ($blogPost !== null && $blogPost->exists) {
            $data = array_merge($blogPost->only([
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
            ]), $data);

            if (! array_key_exists('tag_ids', $data)) {
                $data['tag_ids'] = $blogPost->tags()->pluck('blog_tags.id')->all();
            }

            return $this->updateBlogPost->handle($blogPost, $data);
        }

        return $this->createBlogPost->handle($data);
    }

    /**
     * @return list<int>
     */
    private function tagIdsFromNames(mixed $tags): array
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
}
