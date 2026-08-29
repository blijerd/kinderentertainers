<?php

namespace App\Actions;

use App\Models\Entertainer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UpdateEntertainerProfile
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(Entertainer $entertainer, array $validated, Request $request): Entertainer
    {
        $validated['profile_highlights'] = collect(preg_split('/\r\n|\r|\n/', $validated['profile_highlights'] ?? ''))
            ->map(fn (string $highlight): string => trim($highlight))
            ->filter()
            ->values()
            ->all();
        $validated['event_types'] = $this->linesToArray($validated['event_types'] ?? '');
        $validated['languages'] = $this->linesToArray($validated['languages'] ?? '');
        $validated['packages'] = $this->linesToOfferItems($validated['packages'] ?? '');
        $validated['extras'] = $this->linesToOfferItems($validated['extras'] ?? '');

        unset($validated['profile_photo'], $validated['gallery_photos'], $validated['remove_gallery_photos']);

        $galleryPhotoPaths = collect($entertainer->gallery_photo_paths ?? []);
        $removedGalleryPhotoPaths = collect($request->input('remove_gallery_photos', []))
            ->filter()
            ->intersect($galleryPhotoPaths)
            ->values();
        $newGalleryPhotos = $request->file('gallery_photos', []);

        if ($galleryPhotoPaths->count() - $removedGalleryPhotoPaths->count() + count($newGalleryPhotos) > 12) {
            throw ValidationException::withMessages([
                'gallery_photos' => 'Je kunt maximaal 12 galerijfoto\'s bewaren.',
            ]);
        }

        if ($request->hasFile('profile_photo')) {
            if ($entertainer->profile_photo_path) {
                Storage::disk('public')->delete($entertainer->profile_photo_path);
            }

            $validated['profile_photo_path'] = $request->file('profile_photo')->store('entertainers/profile-photos', 'public');
        }

        if ($removedGalleryPhotoPaths->isNotEmpty()) {
            Storage::disk('public')->delete($removedGalleryPhotoPaths->all());
            $galleryPhotoPaths = $galleryPhotoPaths->reject(fn (string $path): bool => $removedGalleryPhotoPaths->contains($path));
        }

        foreach ($newGalleryPhotos as $galleryPhoto) {
            $galleryPhotoPaths->push($galleryPhoto->store('entertainers/gallery', 'public'));
        }

        $validated['gallery_photo_paths'] = $galleryPhotoPaths->values()->all();

        $entertainer->update($validated);

        return $entertainer->refresh();
    }

    private function linesToArray(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<array{name: string, price_cents: int|null, description: string|null}>
     */
    private function linesToOfferItems(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->map(function (string $line): array {
                [$name, $price, $description] = array_pad(array_map('trim', explode('|', $line, 3)), 3, null);

                return [
                    'name' => $name,
                    'price_cents' => filled($price) ? (int) round(((float) str_replace(',', '.', $price)) * 100) : null,
                    'description' => $description,
                ];
            })
            ->values()
            ->all();
    }
}
