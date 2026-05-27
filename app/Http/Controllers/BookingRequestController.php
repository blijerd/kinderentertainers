<?php

namespace App\Http\Controllers;

use App\Actions\CreateBookingRequest;
use App\Http\Requests\StoreBookingRequestRequest;
use App\Models\Entertainer;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BookingRequestController extends Controller
{
    public function create(?Entertainer $entertainer = null): View
    {
        if ($entertainer !== null) {
            abort_unless($entertainer->active, 404);
            $entertainer->load('skills');
        }

        return view('booking-requests.create', [
            'entertainer' => $entertainer,
            'skills' => Skill::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(
        StoreBookingRequestRequest $request,
        CreateBookingRequest $createBookingRequest,
        ?Entertainer $entertainer = null,
    ): RedirectResponse {
        if ($entertainer !== null) {
            abort_unless($entertainer->active, 404);
        }

        $validated = $request->validated();
        $requestType = $validated['request_type'];
        unset($validated['request_type']);

        if ($requestType === 'specific') {
            abort_unless($entertainer !== null, 404);

            $validated['entertainer_id'] = $entertainer->id;
            $validated['skill_id'] ??= $entertainer->skills()->where('active', true)->value('skills.id');
        } else {
            $validated['entertainer_id'] = null;
        }

        if ($request->user()?->hasRole('klant')) {
            $validated['customer_id'] = $request->user()->id;
        }

        $createBookingRequest->handle($validated);

        return redirect()->route('booking-requests.thanks');
    }

    public function thanks(): View
    {
        return view('booking-requests.thanks');
    }
}
