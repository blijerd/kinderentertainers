<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        $posts = BlogPost::query()
            ->published()
            ->with(['tags', 'author'])
            ->latestPublished()
            ->paginate(10)
            ->withQueryString();

        $tags = BlogTag::query()
            ->withPublishedPosts()
            ->orderBy('name')
            ->get();

        return view('blog.index', [
            'posts' => $posts,
            'tags' => $tags,
            'canonicalUrl' => $posts->currentPage() === 1
                ? route('blog.index')
                : $posts->url($posts->currentPage()),
            'blogSchema' => [
                '@context' => 'https://schema.org',
                '@type' => 'Blog',
                'name' => 'Blog — Kinderentertainers.nl',
                'description' => 'Tips en achtergrond over kinderentertainers, kinderfeestjes en boeken via Kinderentertainers.nl.',
                'url' => route('blog.index'),
                'inLanguage' => 'nl-NL',
            ],
            'breadcrumbSchema' => [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => route('blog.index')],
                ],
            ],
        ]);
    }

    public function show(BlogPost $blogPost): View
    {
        abort_unless(BlogPost::query()->published()->whereKey($blogPost->getKey())->exists(), 404);

        $blogPost->load(['tags', 'author']);

        return view('blog.show', [
            'post' => $blogPost,
            'relatedPosts' => $blogPost->relatedPublishedPosts(),
        ]);
    }

    public function tag(BlogTag $blogTag): View
    {
        $posts = $blogTag->posts()
            ->published()
            ->with(['tags', 'author'])
            ->latestPublished()
            ->paginate(10)
            ->withQueryString();

        return view('blog.tag', [
            'tag' => $blogTag,
            'posts' => $posts,
            'canonicalUrl' => $posts->currentPage() === 1
                ? route('blog.tag', $blogTag)
                : $posts->url($posts->currentPage()),
        ]);
    }

    public function feed(): Response
    {
        $posts = BlogPost::query()
            ->indexable()
            ->with('author')
            ->latestPublished()
            ->limit(20)
            ->get();

        $xml = view('blog.feed', [
            'posts' => $posts,
            'updatedAt' => $posts->first()?->updated_at,
        ])->render();

        return response(trim($xml), 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }
}
