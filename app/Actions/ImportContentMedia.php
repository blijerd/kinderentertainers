<?php

namespace App\Actions;

use App\Models\ContentMedia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ImportContentMedia
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(string $absolutePath, array $data = []): ContentMedia
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            throw ValidationException::withMessages([
                'file' => 'Het mediabestand bestaat niet of is niet leesbaar.',
            ]);
        }

        $extension = strtolower((string) pathinfo($absolutePath, PATHINFO_EXTENSION));
        $allowed = config('content.allowed_media_extensions', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

        if (! in_array($extension, $allowed, true)) {
            throw ValidationException::withMessages([
                'file' => 'Alleen JPG, PNG, GIF of WebP zijn toegestaan.',
            ]);
        }

        $maxKilobytes = (int) config('content.max_media_kilobytes', 8192);
        $byteSize = (int) filesize($absolutePath);

        if ($byteSize < 1 || $byteSize > ($maxKilobytes * 1024)) {
            throw ValidationException::withMessages([
                'file' => "Het mediabestand is groter dan {$maxKilobytes} kB.",
            ]);
        }

        $checksum = hash_file('sha256', $absolutePath);

        if (! is_string($checksum) || $checksum === '') {
            throw new RuntimeException('Could not hash the media file.');
        }

        $originalFilename = $data['original_filename'] ?? basename($absolutePath);
        $sourcePath = $data['source_path'] ?? null;
        $altText = $data['alt_text'] ?? $data['alt'] ?? null;
        $disk = (string) ($data['disk'] ?? config('content.media_disk', 'public'));

        $media = null;

        if (is_string($sourcePath) && $sourcePath !== '') {
            $media = ContentMedia::withTrashed()->where('source_path', $sourcePath)->first();
        }

        $media ??= ContentMedia::withTrashed()->where('checksum', $checksum)->first();
        $media ??= new ContentMedia;

        if ($media->exists && $media->trashed()) {
            $media->restore();
        }

        if (! filled($media->public_id)) {
            $media->forceFill(['public_id' => (string) Str::uuid()]);
        }

        if ($media->exists && $media->checksum === $checksum && is_string($media->path) && $media->path !== '') {
            $media->fill([
                'original_filename' => $originalFilename,
                'alt_text' => is_string($altText) && $altText !== '' ? $altText : $media->alt_text,
                'source_path' => is_string($sourcePath) && $sourcePath !== '' ? $sourcePath : $media->source_path,
            ])->save();

            return $media->refresh();
        }

        $contents = file_get_contents($absolutePath);

        if ($contents === false) {
            throw new RuntimeException('Could not read the media file.');
        }

        $storedFilename = $media->public_id.'-'.Str::slug((string) pathinfo((string) $originalFilename, PATHINFO_FILENAME)).'.'.$extension;
        $path = trim((string) config('content.media_directory_on_disk', 'content-media'), '/').'/'.$storedFilename;

        if ($media->exists && is_string($media->path) && $media->path !== '' && $media->path !== $path) {
            Storage::disk($media->disk ?: $disk)->delete($media->path);
        }

        Storage::disk($disk)->put($path, $contents);

        $media->fill([
            'original_filename' => $originalFilename,
            'path' => $path,
            'disk' => $disk,
            'mime_type' => $this->guessMimeType($absolutePath, $extension),
            'byte_size' => $byteSize,
            'checksum' => $checksum,
            'alt_text' => is_string($altText) && $altText !== '' ? $altText : $media->alt_text,
            'source_path' => is_string($sourcePath) && $sourcePath !== '' ? $sourcePath : $media->source_path,
        ])->save();

        return $media->refresh();
    }

    private function guessMimeType(string $absolutePath, string $extension): string
    {
        $detected = mime_content_type($absolutePath);

        if (is_string($detected) && $detected !== '') {
            return $detected;
        }

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }
}
