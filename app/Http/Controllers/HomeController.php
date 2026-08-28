<?php

namespace App\Http\Controllers;

use App\Models\Entertainer;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $active = Entertainer::query()->where('active', true);

        return view('home', [
            'entertainerCount' => (clone $active)->count(),
            'averageRating' => (clone $active)
                ->where('reviews_count', '>', 0)
                ->whereNotNull('rating')
                ->avg('rating'),
        ]);
    }
}
