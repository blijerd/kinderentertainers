<?php

namespace App\Actions;

use App\Models\BlogPost;
use App\Models\ContentRedirect;
use App\Models\LandingPage;
use App\Support\Content\ContentRedirectPath;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpsertContentRedirect
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(?ContentRedirect $redirect, array $data): ContentRedirect
    {
        $fromPath = ContentRedirectPath::normalizeFrom($data['from_path'] ?? $data['from'] ?? $redirect?->from_path ?? '');

        if ($redirect === null && $fromPath !== '' && $fromPath !== '/') {
            $redirect = ContentRedirect::withTrashed()->where('from_path', $fromPath)->first();
        }

        $isCreate = $redirect === null || ! $redirect->exists;

        $payload = [
            ...$data,
            'from_path' => $fromPath,
            'to_url' => ContentRedirectPath::normalizeTo($data['to_url'] ?? $data['to'] ?? $redirect?->to_url ?? ''),
            'status_code' => (int) ($data['status_code'] ?? $data['status'] ?? $redirect?->status_code ?? 301),
        ];

        $validated = validator($payload, [
            'from_path' => [
                'required',
                'string',
                'max:255',
                'regex:/^\/[a-z0-9\/._-]*$/',
                Rule::unique('content_redirects', 'from_path')->ignore($redirect)->whereNull('deleted_at'),
            ],
            'to_url' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:2048'],
            'status_code' => ['sometimes', 'integer', Rule::in([301, 302, 307, 308])],
            'is_active' => ['sometimes', 'boolean'],
            'source_path' => ['nullable', 'string', 'max:255'],
        ], [
            'from_path.regex' => 'Het bronpad mag alleen kleine letters, cijfers, streepjes en slashes bevatten.',
        ])->validate();

        $validated['from_path'] = ContentRedirectPath::normalizeFrom($validated['from_path']);
        $validated['to_url'] = ContentRedirectPath::normalizeTo($validated['to_url'] ?? $redirect?->to_url ?? '');

        if ($validated['from_path'] === '/' || ContentRedirectPath::isReservedFrom($validated['from_path'])) {
            throw ValidationException::withMessages([
                'from_path' => 'Dit bronpad is gereserveerd voor een systeemroute.',
            ]);
        }

        if ($validated['to_url'] === '') {
            throw ValidationException::withMessages([
                'to_url' => 'Een doelpad of URL is verplicht.',
            ]);
        }

        if (! ContentRedirectPath::isAbsoluteUrl($validated['to_url']) && ! preg_match('/^\/[a-z0-9\/._-]*$/', $validated['to_url'])) {
            throw ValidationException::withMessages([
                'to_url' => 'Het doel moet een intern pad of een http(s)-URL zijn.',
            ]);
        }

        if (ContentRedirectPath::destinationPath($validated['to_url']) === $validated['from_path']) {
            throw ValidationException::withMessages([
                'to_url' => 'Een redirect mag niet naar het eigen bronpad wijzen.',
            ]);
        }

        $this->assertSourceIsAvailable($validated['from_path']);

        $redirect ??= new ContentRedirect;

        if ($redirect->exists && $redirect->trashed()) {
            $redirect->restore();
        }

        $redirect->fill($validated)->save();

        return $redirect->refresh();
    }

    private function assertSourceIsAvailable(string $fromPath): void
    {
        $slug = ltrim($fromPath, '/');

        if (! str_contains($slug, '/') && LandingPage::query()->published()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'from_path' => 'Er staat al een gepubliceerde landingspagina op dit pad. Depubliceer die eerst of kies een ander bronpad.',
            ]);
        }

        if (str_starts_with($fromPath, '/blog/') && BlogPost::query()->published()->where('slug', substr($fromPath, 6))->exists()) {
            throw ValidationException::withMessages([
                'from_path' => 'Er staat al een gepubliceerd blogartikel op dit pad. Depubliceer dat eerst of kies een ander bronpad.',
            ]);
        }
    }
}
