<?php

namespace Tests\Feature;

use App\Actions\CreateBlogPost;
use App\Actions\CreateBlogTag;
use App\Actions\DeleteBlogPost;
use App\Actions\UpdateBlogPost;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use App\Support\Models\HasPublicIdentifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_post_renders_with_seo_metadata_and_schema(): void
    {
        $tag = BlogTag::factory()->create([
            'name' => 'Kinderfeestje',
            'slug' => 'kinderfeestje',
        ]);
        $post = BlogPost::factory()->create([
            'title' => 'Hoe boek je een kinderentertainer?',
            'slug' => 'hoe-boek-je-een-kinderentertainer',
            'intro' => 'Zo regel je een act zonder gedoe.',
            'body' => '## Concrete aanvraag'.PHP_EOL.PHP_EOL.'Vermeld datum en locatie.',
            'seo_title' => 'Kinderentertainer boeken',
            'meta_description' => 'Leer hoe je een kinderentertainer boekt.',
            'is_published' => true,
            'published_at' => now()->subMinute(),
        ]);
        $post->tags()->attach($tag);

        $url = route('blog.show', $post);

        $this->get($url)
            ->assertOk()
            ->assertSee('<title>Kinderentertainer boeken</title>', false)
            ->assertSee('name="description" content="Leer hoe je een kinderentertainer boekt."', false)
            ->assertSee('rel="canonical" href="'.$url.'"', false)
            ->assertSee('property="og:type" content="article"', false)
            ->assertSee('"@type":"BlogPosting"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('Hoe boek je een kinderentertainer?')
            ->assertSee('Vermeld datum en locatie.')
            ->assertSee('Kinderfeestje')
            ->assertDontSee('/blog/'.$post->getKey());

        $this->assertStringNotContainsString('/blog/'.$post->getKey(), $url);
        $this->assertSame('slug', $post->getRouteKeyName());
        $this->assertNotNull($post->public_id);
    }

    public function test_unpublished_and_scheduled_posts_are_not_public(): void
    {
        $draft = BlogPost::factory()->unpublished()->create([
            'slug' => 'concept-artikel',
        ]);
        $scheduled = BlogPost::factory()->scheduled()->create([
            'slug' => 'ingepland-artikel',
        ]);

        $this->get(route('blog.show', $draft))->assertNotFound();
        $this->get(route('blog.show', $scheduled))->assertNotFound();
        $this->get(route('blog.index'))
            ->assertOk()
            ->assertDontSee($draft->title)
            ->assertDontSee($scheduled->title);
    }

    public function test_blog_index_lists_published_posts_and_tags(): void
    {
        $tag = BlogTag::factory()->create(['name' => 'Schminken', 'slug' => 'schminken']);
        $visible = BlogPost::factory()->create([
            'title' => 'Schminken op een kinderfeestje',
            'slug' => 'schminken-op-een-kinderfeestje',
        ]);
        $hidden = BlogPost::factory()->unpublished()->create([
            'title' => 'Verborgen concept',
            'slug' => 'verborgen-concept',
        ]);
        $visible->tags()->attach($tag);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee('<title>Blog — Kinderentertainers.nl</title>', false)
            ->assertSee('rel="canonical" href="'.route('blog.index').'"', false)
            ->assertSee('"@type":"Blog"', false)
            ->assertSee($visible->title)
            ->assertSee('Schminken')
            ->assertDontSee($hidden->title);
    }

    public function test_tag_archive_filters_published_posts(): void
    {
        $matchTag = BlogTag::factory()->create(['name' => 'Boeken', 'slug' => 'boeken']);
        $otherTag = BlogTag::factory()->create(['name' => 'Goochelen', 'slug' => 'goochelen']);
        $match = BlogPost::factory()->create(['title' => 'Aanvragen zonder gedoe', 'slug' => 'aanvragen-zonder-gedoe']);
        $other = BlogPost::factory()->create(['title' => 'Goochelshow plannen', 'slug' => 'goochelshow-plannen']);
        $match->tags()->attach($matchTag);
        $other->tags()->attach($otherTag);

        $this->get(route('blog.tag', $matchTag))
            ->assertOk()
            ->assertSee('Aanvragen zonder gedoe')
            ->assertDontSee('Goochelshow plannen')
            ->assertSee('"@type":"CollectionPage"', false);
    }

    public function test_noindex_post_is_public_but_excluded_from_sitemap_and_feed(): void
    {
        $indexable = BlogPost::factory()->create([
            'title' => 'Indexeerbaar artikel',
            'slug' => 'indexeerbaar-artikel',
        ]);
        $hidden = BlogPost::factory()->noindex()->create([
            'title' => 'Niet indexeren',
            'slug' => 'niet-indexeren',
        ]);

        $this->get(route('blog.show', $hidden))
            ->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow"', false);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(route('blog.index'))
            ->assertSee(route('blog.show', $indexable))
            ->assertDontSee(route('blog.show', $hidden));

        $this->get(route('blog.feed'))
            ->assertOk()
            ->assertSee($indexable->title)
            ->assertDontSee($hidden->title);

        $this->assertStringStartsWith('application/rss+xml', (string) $this->get(route('blog.feed'))->headers->get('content-type'));
    }

    public function test_create_blog_post_action_generates_slug_and_syncs_tags(): void
    {
        $author = User::factory()->create();
        $tag = app(CreateBlogTag::class)->handle([
            'name' => 'Kinder-DJ',
            'description' => 'Artikelen over kinder-DJ’s.',
        ]);

        $post = app(CreateBlogPost::class)->handle([
            'author_id' => $author->id,
            'title' => 'Kinder-DJ boeken voor een schoolfeest',
            'intro' => 'Wat je doorgeeft voor een soepele DJ-act.',
            'body' => 'Geef de leeftijdsgroep en zaalinfo door.',
            'is_published' => true,
            'tag_ids' => [$tag->id],
        ]);

        $this->assertSame('kinder-dj-boeken-voor-een-schoolfeest', $post->slug);
        $this->assertTrue($post->is_published);
        $this->assertNotNull($post->published_at);
        $this->assertTrue($post->tags->contains($tag));
        $this->assertSame('kinder-dj', $tag->slug);
        $this->assertContains(HasPublicIdentifier::class, class_uses_recursive($post));
    }

    public function test_create_blog_post_rejects_duplicate_slugs(): void
    {
        BlogPost::factory()->create(['slug' => 'bestaande-slug']);

        try {
            app(CreateBlogPost::class)->handle([
                'title' => 'Tweede artikel',
                'slug' => 'bestaande-slug',
                'is_published' => false,
            ]);
            $this->fail('Expected duplicate slug validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('slug', $exception->errors());
        }
    }

    public function test_update_and_delete_blog_post_go_through_actions(): void
    {
        $post = BlogPost::factory()->create([
            'title' => 'Oude titel',
            'slug' => 'oude-titel',
            'is_published' => false,
        ]);

        $updated = app(UpdateBlogPost::class)->handle($post, [
            'title' => 'Nieuwe titel',
            'slug' => 'oude-titel',
            'is_published' => true,
        ]);

        $this->assertSame('Nieuwe titel', $updated->title);
        $this->assertTrue($updated->is_published);
        $this->assertNotNull($updated->published_at);

        app(DeleteBlogPost::class)->handle($updated);

        $this->assertSoftDeleted($updated);
    }
}
