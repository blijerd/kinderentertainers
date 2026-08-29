<?php

namespace App\Actions\Concerns;

use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Support\Content\ReservedPublicSlugs;
use App\Support\UniqueSlug;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

trait PreparesBlogPostData
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: list<int>}
     */
    protected function prepareBlogPostData(array $data, ?BlogPost $existing = null): array
    {
        $tagIds = $this->normalizeTagIds($data['tag_ids'] ?? []);
        unset($data['tag_ids'], $data['tags']);

        if (! array_key_exists('intro', $data) && array_key_exists('excerpt', $data)) {
            $data['intro'] = $data['excerpt'];
        }

        if (! array_key_exists('cover_image_path', $data) && array_key_exists('featured_image_path', $data)) {
            $data['cover_image_path'] = $data['featured_image_path'];
        }

        $providedSlug = UniqueSlug::normalize(isset($data['slug']) ? (string) $data['slug'] : null);
        $data['slug'] = $providedSlug !== ''
            ? $providedSlug
            : UniqueSlug::from(BlogPost::class, (string) ($data['title'] ?? ''), $existing, 'artikel');

        if (in_array($data['slug'], ['tag', 'feed'], true) || ReservedPublicSlugs::isReserved($data['slug'])) {
            $data['slug'] = UniqueSlug::from(BlogPost::class, $data['slug'].'-artikel', $existing, 'artikel');
        }

        $data['is_published'] = (bool) ($data['is_published'] ?? $existing?->is_published ?? false);
        $data['noindex'] = (bool) ($data['noindex'] ?? $existing?->noindex ?? false);

        if ($data['is_published'] && blank($data['published_at'] ?? null) && blank($existing?->published_at)) {
            $data['published_at'] = now();
        }

        $payload = Arr::only($data, [
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
        ]);

        Validator::make(
            [...$payload, 'tag_ids' => $tagIds],
            [
                'title' => ['required', 'string', 'max:255'],
                'slug' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                    'not_in:tag,feed',
                    Rule::notIn(ReservedPublicSlugs::all()),
                    Rule::unique('blog_posts', 'slug')->ignore($existing),
                ],
                'intro' => ['nullable', 'string', 'max:500'],
                'body' => ['nullable', 'string'],
                'seo_title' => ['nullable', 'string', 'max:255'],
                'meta_description' => ['nullable', 'string', 'max:320'],
                'canonical_url' => ['nullable', 'url', 'max:255'],
                'cover_image_path' => ['nullable', 'string', 'max:255'],
                'og_image_path' => ['nullable', 'string', 'max:255'],
                'source_path' => ['nullable', 'string', 'max:255'],
                'author_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
                'noindex' => ['boolean'],
                'is_published' => ['boolean'],
                'published_at' => ['nullable', 'date'],
                'tag_ids' => ['array'],
                'tag_ids.*' => ['integer', Rule::exists('blog_tags', 'id')],
            ],
        )->validate();

        return [$payload, $tagIds];
    }

    /**
     * @return list<int>
     */
    protected function normalizeTagIds(mixed $tagIds): array
    {
        if (! is_array($tagIds)) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): int => (int) $id, $tagIds),
            static fn (int $id): bool => $id > 0,
        )));

        return BlogTag::query()->whereIn('id', $ids)->pluck('id')->all();
    }
}
