<?php

namespace App\Http\Controllers;

use App\Models\Entertainer;
use Illuminate\View\View;

class EntertainerController extends Controller
{
    public function index(): View
    {
        return view('entertainers.index');
    }

    public function show(Entertainer $entertainer): View
    {
        abort_unless($entertainer->active, 404);

        $entertainer->load(['skills', 'rates', 'availabilities' => fn ($query) => $query->upcoming()->orderBy('date')]);

        return view('entertainers.show', compact('entertainer'));
    }
}
