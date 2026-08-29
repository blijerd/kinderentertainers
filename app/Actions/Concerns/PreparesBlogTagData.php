<?php

namespace App\Actions\Concerns;

use App\Models\BlogTag;
use App\Support\UniqueSlug;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

trait PreparesBlogTagData
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareBlogTagData(array $data, ?BlogTag $existing = null): array
    {
        $providedSlug = UniqueSlug::normalize(isset($data['slug']) ? (string) $data['slug'] : null);
        $data['slug'] = $providedSlug !== ''
            ? $providedSlug
            : UniqueSlug::from(BlogTag::class, (string) ($data['name'] ?? ''), $existing, 'onderwerp');

        $data['noindex'] = (bool) ($data['noindex'] ?? $existing?->noindex ?? false);

        $payload = Arr::only($data, [
            'name',
            'slug',
            'description',
            'seo_title',
            'meta_description',
            'noindex',
        ]);

        Validator::make($payload, [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('blog_tags', 'slug')->ignore($existing),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'noindex' => ['boolean'],
        ])->validate();

        return $payload;
    }
}
