<?php

namespace App\Http\Controllers;

use App\Actions\CreateBookingRequest;
use App\Http\Requests\StoreBookingRequestRequest;
use App\Models\Entertainer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BookingRequestController extends Controller
{
    public function create(Entertainer $entertainer): View
    {
        abort_unless($entertainer->active, 404);

        return view('booking-requests.create', compact('entertainer'));
    }

    public function store(
        StoreBookingRequestRequest $request,
        Entertainer $entertainer,
        CreateBookingRequest $createBookingRequest,
    ): RedirectResponse {
        abort_unless($entertainer->active, 404);

        $createBookingRequest->handle([
            ...$request->validated(),
            'entertainer_id' => $entertainer->id,
        ]);

        return redirect()->route('booking-requests.thanks');
    }

    public function thanks(): View
    {
        return view('booking-requests.thanks');
    }
}
