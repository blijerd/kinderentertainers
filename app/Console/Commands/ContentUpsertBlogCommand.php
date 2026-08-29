<?php

namespace App\Console\Commands;

use App\Actions\ImportContentDocument;
use App\Actions\ImportContentMedia;
use App\Actions\UpsertBlogPost;
use App\Models\BlogPost;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class ContentUpsertBlogCommand extends Command
{
    protected $signature = 'content:blog
                            {slug? : Publieke slug van de blogpost}
                            {--file= : Markdownbestand met front matter}
                            {--title= : Titel}
                            {--intro= : Intro}
                            {--body= : Markdown body}
                            {--body-file= : Pad naar een markdown body-bestand}
                            {--seo-title= : SEO-titel}
                            {--meta-description= : Meta description}
                            {--cover-image= : Pad naar een coverfoto}
                            {--tags= : Kommagescheiden tagnamen}
                            {--publish : Publiceren}
                            {--unpublish : Depubliceren}
                            {--noindex : Niet indexeren}';

    protected $description = 'Maak of werk een blogpost bij via de command line.';

    public function handle(
        UpsertBlogPost $upsertBlogPost,
        ImportContentDocument $importContentDocument,
        ImportContentMedia $importContentMedia,
    ): int {
        try {
            $file = $this->option('file');

            if (is_string($file) && $file !== '') {
                $post = $importContentDocument->handle($file, 'blog');
                $this->info("Blogpost {$post->slug} is bijgewerkt: ".route('blog.show', $post));

                return self::SUCCESS;
            }

            $slug = $this->argument('slug');

            if (! is_string($slug) || $slug === '') {
                $this->error('Geef een slug of --file= mee.');

                return self::FAILURE;
            }

            $bodyFile = $this->option('body-file');
            $coverImage = $this->option('cover-image');

            $payload = ['slug' => $slug];

            if ($this->optionWasPassed('title') || $this->option('title')) {
                $payload['title'] = $this->option('title') ?: $slug;
            } elseif (! BlogPost::withTrashed()->where('slug', $slug)->exists()) {
                $payload['title'] = $slug;
            }

            foreach ([
                'intro' => 'intro',
                'body' => 'body',
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

            if (is_string($coverImage) && $coverImage !== '') {
                $payload['cover_image_path'] = is_file($coverImage)
                    ? $importContentMedia->handle($coverImage, ['source_path' => 'media/'.basename($coverImage)])->path
                    : $coverImage;
            }

            if ($this->option('unpublish')) {
                $payload['is_published'] = false;
            } elseif ($this->option('publish')) {
                $payload['is_published'] = true;
            }

            if ($this->optionWasPassed('noindex')) {
                $payload['noindex'] = (bool) $this->option('noindex');
            }

            $tags = $this->option('tags');
            if (is_string($tags) && $tags !== '') {
                $payload['tags'] = $tags;
            }

            $post = $upsertBlogPost->handle(null, $payload);

            $this->info("Blogpost {$post->slug} is bijgewerkt: ".route('blog.show', $post));

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
