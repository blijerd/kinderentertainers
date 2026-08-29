<?php

namespace App\Actions;

use App\Support\Content\ContentFrontMatter;
use App\Support\Content\ContentRedirectPath;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Throwable;

class ImportContentRedirects
{
    public function __construct(
        private readonly UpsertContentRedirect $upsertContentRedirect,
    ) {}

    /**
     * @return array{imported: int, errors: list<string>}
     */
    public function handle(string $contentRoot, bool $dryRun = false): array
    {
        $imported = 0;
        $errors = [];

        foreach ($this->files($contentRoot) as $file) {
            foreach ($this->parseFile($file) as $index => $row) {
                $line = $index + 1;

                try {
                    if (! $dryRun) {
                        $this->upsertContentRedirect->handle(null, [
                            ...$row,
                            'source_path' => $this->relativePath($file, $contentRoot),
                            'is_active' => $row['is_active'] ?? true,
                        ]);
                    }

                    $imported++;
                } catch (ValidationException $exception) {
                    $errors[] = basename($file).':'.$line.': '.$this->firstValidationMessage($exception);
                } catch (Throwable $exception) {
                    $errors[] = basename($file).':'.$line.': '.$exception->getMessage();
                }
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * @return list<string>
     */
    private function files(string $contentRoot): array
    {
        $files = [];
        $rootFile = $contentRoot.DIRECTORY_SEPARATOR.'redirects.txt';

        if (is_file($rootFile)) {
            $files[] = $rootFile;
        }

        $directory = $contentRoot.DIRECTORY_SEPARATOR.(string) config('content.redirects_directory', 'redirects');

        if (is_dir($directory)) {
            foreach (File::files($directory) as $file) {
                if (in_array(strtolower($file->getExtension()), ['txt', 'yml', 'yaml', 'md'], true)
                    && ! in_array($file->getFilename(), ['.gitkeep', '.DS_Store'], true)) {
                    $files[] = $file->getPathname();
                }
            }
        }

        sort($files);

        return $files;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseFile(string $path): array
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            return [];
        }

        $rows = [];

        foreach (preg_split('/\r?\n/', $contents) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (preg_match('/^(from|to|status|status_code|enabled|active):\s*(.*)$/i', $line)) {
                continue;
            }

            if (! preg_match('/^(\S+)\s*(?:->|,)\s*(\S+)(?:\s+(\d{3}))?$/', $line, $matches)
                && ! preg_match('/^(\S+)\s+(\S+)(?:\s+(\d{3}))?$/', $line, $matches)) {
                continue;
            }

            $rows[] = [
                'from_path' => ContentRedirectPath::normalizeFrom($matches[1]),
                'to_url' => ContentRedirectPath::normalizeTo($matches[2]),
                'status_code' => isset($matches[3]) && $matches[3] !== '' ? (int) $matches[3] : 301,
            ];
        }

        $frontMatter = $this->parseFrontMatterFile($contents);

        if ($frontMatter !== null) {
            $rows[] = $frontMatter;
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseFrontMatterFile(string $contents): ?array
    {
        $parsed = ContentFrontMatter::parse($contents);
        $from = $parsed['attributes']['from'] ?? $parsed['attributes']['from_path'] ?? null;
        $to = $parsed['attributes']['to'] ?? $parsed['attributes']['to_url'] ?? null;

        if (! is_string($from) || $from === '' || ! is_string($to) || $to === '') {
            return null;
        }

        return [
            'from_path' => $from,
            'to_url' => $to,
            'status_code' => $parsed['attributes']['status'] ?? $parsed['attributes']['status_code'] ?? 301,
            'is_active' => $parsed['attributes']['enabled'] ?? $parsed['attributes']['active'] ?? $parsed['attributes']['is_active'] ?? true,
        ];
    }

    private function relativePath(string $absolutePath, string $contentRoot): string
    {
        $root = rtrim($contentRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (str_starts_with($absolutePath, $root)) {
            return str_replace(DIRECTORY_SEPARATOR, '/', substr($absolutePath, strlen($root)));
        }

        return basename($absolutePath);
    }

    private function firstValidationMessage(ValidationException $exception): string
    {
        $messages = $exception->validator->errors()->all();

        return $messages[0] ?? $exception->getMessage();
    }
}
