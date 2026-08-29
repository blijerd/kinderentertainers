<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\Entertainer;
use App\Models\LandingPage;
use DateTimeInterface;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'lastmod' => null],
            ['loc' => route('entertainers.index'), 'lastmod' => null],
            ['loc' => route('blog.index'), 'lastmod' => null],
            ['loc' => route('legal.terms'), 'lastmod' => null],
            ['loc' => route('legal.privacy'), 'lastmod' => null],
            ['loc' => route('legal.cookies'), 'lastmod' => null],
        ])
            ->merge(
                Entertainer::query()
                    ->where('active', true)
                    ->get(['slug', 'updated_at'])
                    ->map(fn (Entertainer $entertainer): array => [
                        'loc' => route('entertainers.show', $entertainer),
                        'lastmod' => $entertainer->updated_at,
                    ])
            )
            ->merge(
                LandingPage::query()
                    ->published()
                    ->where('noindex', false)
                    ->get(['slug', 'updated_at'])
                    ->map(fn (LandingPage $landingPage): array => [
                        'loc' => route('landing-pages.show', $landingPage),
                        'lastmod' => $landingPage->updated_at,
                    ])
            )
            ->merge(
                BlogPost::query()
                    ->indexable()
                    ->get(['slug', 'updated_at'])
                    ->map(fn (BlogPost $post): array => [
                        'loc' => route('blog.show', $post),
                        'lastmod' => $post->updated_at,
                    ])
            )
            ->merge(
                BlogTag::query()
                    ->withPublishedPosts()
                    ->where('noindex', false)
                    ->get(['slug', 'updated_at'])
                    ->map(fn (BlogTag $tag): array => [
                        'loc' => route('blog.tag', $tag),
                        'lastmod' => $tag->updated_at,
                    ])
            );

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.e($url['loc'])."</loc>\n";

            $lastmod = $this->lastmod($url['lastmod'] ?? null);
            if ($lastmod !== null) {
                $xml .= '    <lastmod>'.e($lastmod)."</lastmod>\n";
            }

            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    private function lastmod(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface || (is_string($value) && $value !== '')) {
            return Carbon::parse($value)->toAtomString();
        }

        return null;
    }
}
