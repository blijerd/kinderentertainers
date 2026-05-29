<?php

namespace App\Http\Controllers;

use App\Models\Entertainer;
use App\Models\LandingPage;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'lastmod' => null],
            ['loc' => route('entertainers.index'), 'lastmod' => null],
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
            );

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
