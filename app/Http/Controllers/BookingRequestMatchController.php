<?php

namespace App\Http\Controllers;

use App\Actions\SelectBookingRequestMatch;
use App\Enums\BookingRequestMatchStatus;
use App\Models\BookingRequest;
use App\Models\BookingRequestMatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingRequestMatchController extends Controller
{
    public function index(BookingRequest $bookingRequest, string $token): View
    {
        $this->authorizeCustomerToken($bookingRequest, $token);

        $bookingRequest->load([
            'skill',
            'matches' => fn ($query) => $query
                ->with('entertainer')
                ->whereNotNull('responded_at')
                ->where('status', '!=', BookingRequestMatchStatus::Rejected->value)
                ->orderBy('price_indication_cents')
                ->orderBy('responded_at'),
        ]);

        return view('booking-requests.matches.index', compact('bookingRequest', 'token'));
    }

    public function select(
        Request $request,
        BookingRequest $bookingRequest,
        BookingRequestMatch $match,
        SelectBookingRequestMatch $selectBookingRequestMatch,
    ): RedirectResponse {
        $this->authorizeCustomerToken($bookingRequest, $request->string('token')->toString());

        abort_unless($match->booking_request_id === $bookingRequest->id, 404);

        $selectBookingRequestMatch->handle($match);

        return redirect()
            ->route('booking-requests.matches.index', [
                'bookingRequest' => $bookingRequest,
                'token' => $bookingRequest->customer_selection_token,
            ])
            ->with('status', 'Je keuze is opgeslagen. We nemen contact op voor de verdere afstemming.');
    }

    private function authorizeCustomerToken(BookingRequest $bookingRequest, string $token): void
    {
        abort_unless(
            filled($bookingRequest->customer_selection_token)
            && hash_equals($bookingRequest->customer_selection_token, $token),
            404,
        );
    }
}
