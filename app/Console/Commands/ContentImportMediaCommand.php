<?php

namespace App\Console\Commands;

use App\Actions\ImportContentMedia;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class ContentImportMediaCommand extends Command
{
    protected $signature = 'content:media
                            {path : Pad naar de foto}
                            {--alt= : Alternatieve tekst}
                            {--source= : Relatief bronpad, bijvoorbeeld media/hero.jpg}';

    protected $description = 'Importeer een foto naar de contentbibliotheek.';

    public function handle(ImportContentMedia $importContentMedia): int
    {
        $path = $this->argument('path');

        if (! is_string($path) || $path === '') {
            $this->error('Geef een bestandspad mee.');

            return self::FAILURE;
        }

        $source = $this->option('source');
        $alt = $this->option('alt');

        try {
            $media = $importContentMedia->handle($path, [
                'source_path' => is_string($source) && $source !== '' ? $source : 'media/'.basename($path),
                'alt_text' => is_string($alt) && $alt !== '' ? $alt : null,
            ]);
        } catch (ValidationException $exception) {
            $this->error($exception->validator->errors()->first() ?: $exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Foto geïmporteerd: {$media->path} ({$media->url()})");

        return self::SUCCESS;
    }
}
