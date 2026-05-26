<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EntertainerDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $entertainer = $request->user()->entertainer;

        abort_unless($entertainer, 403);

        $entertainer->load(['skills', 'rates', 'availabilities' => fn ($query) => $query->upcoming()->orderBy('date')]);
        $bookingRequests = $entertainer->bookingRequests()->latest()->paginate(10);

        return view('dashboard.index', compact('entertainer', 'bookingRequests'));
    }

    public function updateBookingStatus(Request $request, BookingRequest $bookingRequest): RedirectResponse
    {
        $this->authorize('update', $bookingRequest);

        $validated = $request->validate([
            'status' => ['required', 'in:optie,bevestigd,afgewezen'],
        ]);

        $bookingRequest->update(['status' => BookingStatus::from($validated['status'])]);

        return back()->with('status', 'Aanvraagstatus bijgewerkt.');
    }
}
