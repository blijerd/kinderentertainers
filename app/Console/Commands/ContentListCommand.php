<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Models\ContentMedia;
use App\Models\ContentRedirect;
use App\Models\LandingPage;
use Illuminate\Console\Command;

class ContentListCommand extends Command
{
    protected $signature = 'content:list
                            {type=all : pages, blog, media, redirects of all}';

    protected $description = 'Toon landingspagina\'s, blogposts, foto\'s of redirects in de database.';

    public function handle(): int
    {
        $type = strtolower((string) $this->argument('type'));

        if (! in_array($type, ['all', 'pages', 'blog', 'media', 'redirects'], true)) {
            $this->error('Gebruik pages, blog, media, redirects of all.');

            return self::FAILURE;
        }

        if ($type === 'all' || $type === 'pages') {
            $this->info('Pagina\'s');
            $this->table(
                ['slug', 'title', 'published', 'source'],
                LandingPage::query()->orderBy('slug')->get(['slug', 'title', 'is_published', 'source_path'])
                    ->map(fn (LandingPage $page): array => [
                        $page->slug,
                        $page->title,
                        $page->is_published ? 'ja' : 'nee',
                        $page->source_path ?: '—',
                    ])
                    ->all(),
            );
        }

        if ($type === 'all' || $type === 'blog') {
            $this->info('Blog');
            $this->table(
                ['slug', 'title', 'published', 'source'],
                BlogPost::query()->orderBy('slug')->get(['slug', 'title', 'is_published', 'source_path'])
                    ->map(fn (BlogPost $post): array => [
                        $post->slug,
                        $post->title,
                        $post->is_published ? 'ja' : 'nee',
                        $post->source_path ?: '—',
                    ])
                    ->all(),
            );
        }

        if ($type === 'all' || $type === 'media') {
            $this->info('Foto\'s');
            $this->table(
                ['source', 'path', 'alt'],
                ContentMedia::query()->orderBy('original_filename')->get(['source_path', 'path', 'alt_text'])
                    ->map(fn (ContentMedia $media): array => [
                        $media->source_path ?: '—',
                        $media->path,
                        $media->alt_text ?: '—',
                    ])
                    ->all(),
            );
        }

        if ($type === 'all' || $type === 'redirects') {
            $this->info('Redirects');
            $this->table(
                ['from', 'to', 'status', 'active', 'source'],
                ContentRedirect::query()->orderBy('from_path')->get(['from_path', 'to_url', 'status_code', 'is_active', 'source_path'])
                    ->map(fn (ContentRedirect $redirect): array => [
                        $redirect->from_path,
                        $redirect->to_url,
                        (string) $redirect->status_code,
                        $redirect->is_active ? 'ja' : 'nee',
                        $redirect->source_path ?: '—',
                    ])
                    ->all(),
            );
        }

        return self::SUCCESS;
    }
}
