<?php

namespace App\Actions;

use App\Models\LandingPage;
use App\Support\Content\ReservedPublicSlugs;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpsertLandingPage
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(?LandingPage $landingPage, array $data): LandingPage
    {
        $slug = $this->normalizeSlug($data['slug'] ?? $landingPage?->slug);

        if ($landingPage === null && is_string($slug) && $slug !== '') {
            $landingPage = LandingPage::withTrashed()->where('slug', $slug)->first();
        }

        $isCreate = $landingPage === null || ! $landingPage->exists;

        $validated = validator($data, [
            'title' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'slug' => [
                $isCreate ? 'required' : 'sometimes',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(ReservedPublicSlugs::all()),
                Rule::unique('landing_pages', 'slug')->ignore($landingPage)->whereNull('deleted_at'),
            ],
            'intro' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'cta_label' => ['nullable', 'string', 'max:255'],
            'cta_url' => ['nullable', 'string', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'canonical_url' => ['nullable', 'string', 'max:255'],
            'og_image_path' => ['nullable', 'string', 'max:255'],
            'source_path' => ['nullable', 'string', 'max:255'],
            'noindex' => ['sometimes', 'boolean'],
            'is_published' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ], [
            'slug.not_in' => 'Deze slug is gereserveerd voor een systeemroute.',
        ])->validate();

        $validated['slug'] = $this->normalizeSlug($validated['slug'] ?? $landingPage?->slug);

        if (ReservedPublicSlugs::isReserved($validated['slug'])) {
            throw ValidationException::withMessages([
                'slug' => 'Deze slug is gereserveerd voor een systeemroute.',
            ]);
        }

        $landingPage ??= new LandingPage;

        if ($landingPage->exists && $landingPage->trashed()) {
            $landingPage->restore();
        }

        if (($validated['is_published'] ?? false) && empty($validated['published_at']) && $landingPage->published_at === null) {
            $validated['published_at'] = now();
        }

        $landingPage->fill($validated)->save();

        return $landingPage->refresh();
    }

    private function normalizeSlug(mixed $slug): string
    {
        return Str::slug((string) $slug);
    }
}
