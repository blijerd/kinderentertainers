<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\ContentMedia;
use App\Models\LandingPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentCliTest extends TestCase
{
    use RefreshDatabase;

    private string $contentPath;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->contentPath = storage_path('framework/testing/content-'.uniqid());
        File::makeDirectory($this->contentPath.'/pages', 0755, true);
        File::makeDirectory($this->contentPath.'/blog', 0755, true);
        File::makeDirectory($this->contentPath.'/media', 0755, true);
        config(['content.path' => $this->contentPath]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->contentPath);

        parent::tearDown();
    }

    public function test_content_page_command_creates_and_updates_without_wiping_fields(): void
    {
        $this->artisan('content:page', [
            'slug' => 'schminker-kinderfeestje',
            '--title' => 'Schminker voor kinderfeestje',
            '--intro' => 'Boek een schminker.',
            '--publish' => true,
        ])->assertSuccessful();

        $page = LandingPage::query()->where('slug', 'schminker-kinderfeestje')->firstOrFail();

        $this->assertSame('Schminker voor kinderfeestje', $page->title);
        $this->assertTrue($page->is_published);
        $this->get(route('landing-pages.show', $page))->assertOk()->assertSee('Schminker voor kinderfeestje');

        $this->artisan('content:page', [
            'slug' => 'schminker-kinderfeestje',
            '--intro' => 'Nieuwe intro zonder de titel te wijzigen.',
        ])->assertSuccessful();

        $page->refresh();
        $this->assertSame('Schminker voor kinderfeestje', $page->title);
        $this->assertSame('Nieuwe intro zonder de titel te wijzigen.', $page->intro);
        $this->assertTrue($page->is_published);
    }

    public function test_content_sync_imports_pages_blog_photos_and_tags(): void
    {
        $image = $this->contentPath.'/media/hero.png';
        file_put_contents($image, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));

        File::put($this->contentPath.'/pages/ballonnenclown.md', <<<'MD'
---
title: Ballonnenclown boeken
slug: ballonnenclown-boeken
intro: Boek een ballonnenclown.
cta_label: Bekijk entertainers
cta_url: /kinderentertainers
og_image: media/hero.png
published: true
---

Kijk naar de ![hero](media/hero.png) foto.
MD);

        File::put($this->contentPath.'/blog/eerste-artikel.md', <<<'MD'
---
title: Eerste blogartikel
slug: eerste-blogartikel
intro: Tips voor een kinderfeest.
tags: Kinderfeestje, Schminken
published: true
---

Een artikel over boeken.
MD);

        $this->artisan('content:sync', ['--path' => $this->contentPath])
            ->assertSuccessful();

        $page = LandingPage::query()->where('slug', 'ballonnenclown-boeken')->firstOrFail();
        $post = BlogPost::query()->where('slug', 'eerste-blogartikel')->firstOrFail();
        $media = ContentMedia::query()->where('source_path', 'media/hero.png')->firstOrFail();

        $this->assertTrue($page->is_published);
        $this->assertSame($media->path, $page->og_image_path);
        $this->assertStringContainsString('/storage/'.$media->path, (string) $page->body);
        $this->assertTrue($post->is_published);
        $this->assertTrue($post->tags->pluck('name')->contains('Kinderfeestje'));
        $this->assertTrue($post->tags->pluck('name')->contains('Schminken'));

        $this->get(route('landing-pages.show', $page))->assertOk()->assertSee('Ballonnenclown boeken');
        $this->get(route('blog.show', $post))->assertOk()->assertSee('Eerste blogartikel')->assertSee('Kinderfeestje');
    }

    public function test_reserved_landing_page_slug_is_rejected(): void
    {
        $this->artisan('content:page', [
            'slug' => 'blog',
            '--title' => 'Mag niet',
        ])->assertFailed();

        $this->assertDatabaseMissing('landing_pages', ['slug' => 'blog']);
    }

    public function test_content_media_command_imports_a_photo(): void
    {
        $image = $this->contentPath.'/media/clown.png';
        file_put_contents($image, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));

        $this->artisan('content:media', [
            'path' => $image,
            '--alt' => 'Clown',
        ])->assertSuccessful();

        $media = ContentMedia::query()->where('original_filename', 'clown.png')->firstOrFail();
        $this->assertSame('Clown', $media->alt_text);
        Storage::disk('public')->assertExists($media->path);
    }
}
