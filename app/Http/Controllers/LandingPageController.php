<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function show(LandingPage $landingPage): View
    {
        abort_unless(LandingPage::query()->published()->whereKey($landingPage->getKey())->exists(), 404);

        return view('landing-pages.show', compact('landingPage'));
    }
}
