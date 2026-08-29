<?php

namespace App\Console\Commands;

use App\Actions\ImportContentDocument;
use App\Actions\ImportContentMedia;
use App\Actions\UpsertLandingPage;
use App\Models\LandingPage;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class ContentUpsertPageCommand extends Command
{
    protected $signature = 'content:page
                            {slug? : Publieke slug van de pagina}
                            {--file= : Markdownbestand met front matter}
                            {--title= : Titel}
                            {--intro= : Intro}
                            {--body= : Markdown body}
                            {--body-file= : Pad naar een markdown body-bestand}
                            {--cta-label= : CTA-label}
                            {--cta-url= : CTA-URL}
                            {--seo-title= : SEO-titel}
                            {--meta-description= : Meta description}
                            {--og-image= : Pad naar een OG-afbeelding}
                            {--publish : Publiceren}
                            {--unpublish : Depubliceren}
                            {--noindex : Niet indexeren}';

    protected $description = 'Maak of werk een landingspagina bij via de command line.';

    public function handle(
        UpsertLandingPage $upsertLandingPage,
        ImportContentDocument $importContentDocument,
        ImportContentMedia $importContentMedia,
    ): int {
        try {
            $file = $this->option('file');

            if (is_string($file) && $file !== '') {
                $page = $importContentDocument->handle($file, 'page');
                $this->info("Pagina {$page->slug} is bijgewerkt: ".route('landing-pages.show', $page));

                return self::SUCCESS;
            }

            $slug = $this->argument('slug');

            if (! is_string($slug) || $slug === '') {
                $this->error('Geef een slug of --file= mee.');

                return self::FAILURE;
            }

            $bodyFile = $this->option('body-file');
            $ogImage = $this->option('og-image');

            $payload = ['slug' => $slug];

            if ($this->optionWasPassed('title') || $this->option('title')) {
                $payload['title'] = $this->option('title') ?: $slug;
            } elseif (! LandingPage::withTrashed()->where('slug', $slug)->exists()) {
                $payload['title'] = $slug;
            }

            foreach ([
                'intro' => 'intro',
                'body' => 'body',
                'cta-label' => 'cta_label',
                'cta-url' => 'cta_url',
                'seo-title' => 'seo_title',
                'meta-description' => 'meta_description',
            ] as $option => $field) {
                if ($this->optionWasPassed($option)) {
                    $payload[$field] = $this->nullableOption($option);
                }
            }

            if (is_string($bodyFile) && $bodyFile !== '') {
                $payload['body'] = $this->readFile($bodyFile);
            }

            if (is_string($ogImage) && $ogImage !== '') {
                $payload['og_image_path'] = is_file($ogImage)
                    ? $importContentMedia->handle($ogImage, ['source_path' => 'media/'.basename($ogImage)])->path
                    : $ogImage;
            }

            if ($this->option('unpublish')) {
                $payload['is_published'] = false;
            } elseif ($this->option('publish')) {
                $payload['is_published'] = true;
            }

            if ($this->optionWasPassed('noindex')) {
                $payload['noindex'] = (bool) $this->option('noindex');
            }

            $page = $upsertLandingPage->handle(null, $payload);

            $this->info("Pagina {$page->slug} is bijgewerkt: ".url('/'.$page->slug));

            return self::SUCCESS;
        } catch (ValidationException $exception) {
            $this->error($exception->validator->errors()->first() ?: $exception->getMessage());

            return self::FAILURE;
        }
    }

    private function optionWasPassed(string $name): bool
    {
        return $this->input->hasParameterOption('--'.$name);
    }

    private function nullableOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function readFile(string $path): string
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw ValidationException::withMessages([
                'body-file' => 'Het body-bestand kon niet worden gelezen.',
            ]);
        }

        return $contents;
    }
}
