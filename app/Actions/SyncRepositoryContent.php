<?php

namespace App\Actions;

use App\Support\Content\ContentSyncReport;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Throwable;

class SyncRepositoryContent
{
    public function __construct(
        private readonly ImportContentMedia $importContentMedia,
        private readonly ImportContentDocument $importContentDocument,
    ) {}

    public function handle(?string $path = null, bool $dryRun = false): ContentSyncReport
    {
        $root = rtrim($path ?: (string) config('content.path'), DIRECTORY_SEPARATOR);
        $report = new ContentSyncReport(dryRun: $dryRun);

        if (! is_dir($root)) {
            return $report;
        }

        $mediaDirectory = $root.DIRECTORY_SEPARATOR.(string) config('content.media_directory', 'media');
        $pagesDirectory = $root.DIRECTORY_SEPARATOR.(string) config('content.pages_directory', 'pages');
        $blogDirectory = $root.DIRECTORY_SEPARATOR.(string) config('content.blog_directory', 'blog');

        foreach ($this->files($mediaDirectory, config('content.allowed_media_extensions', [])) as $file) {
            try {
                if (! $dryRun) {
                    $this->importContentMedia->handle($file, [
                        'source_path' => 'media/'.basename($file),
                    ]);
                }

                $report->media++;
            } catch (Throwable $exception) {
                $report->errors[] = basename($file).': '.$exception->getMessage();
            }
        }

        foreach ($this->files($pagesDirectory, ['md', 'markdown']) as $file) {
            try {
                if (! $dryRun) {
                    $this->importContentDocument->handle($file, 'page', $root);
                }

                $report->pages++;
            } catch (ValidationException $exception) {
                $report->errors[] = basename($file).': '.$this->firstValidationMessage($exception);
            } catch (Throwable $exception) {
                $report->errors[] = basename($file).': '.$exception->getMessage();
            }
        }

        foreach ($this->files($blogDirectory, ['md', 'markdown']) as $file) {
            try {
                if (! $dryRun) {
                    $this->importContentDocument->handle($file, 'blog', $root);
                }

                $report->posts++;
            } catch (ValidationException $exception) {
                $report->errors[] = basename($file).': '.$this->firstValidationMessage($exception);
            } catch (Throwable $exception) {
                $report->errors[] = basename($file).': '.$exception->getMessage();
            }
        }

        return $report;
    }

    /**
     * @param  list<string>  $extensions
     * @return list<string>
     */
    private function files(string $directory, array $extensions): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $files = [];

        foreach (File::files($directory) as $file) {
            $extension = strtolower($file->getExtension());

            if ($extensions !== [] && ! in_array($extension, $extensions, true)) {
                continue;
            }

            if (in_array($file->getFilename(), ['.gitkeep', '.DS_Store'], true)) {
                continue;
            }

            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }

    private function firstValidationMessage(ValidationException $exception): string
    {
        $messages = $exception->validator->errors()->all();

        return $messages[0] ?? $exception->getMessage();
    }
}
