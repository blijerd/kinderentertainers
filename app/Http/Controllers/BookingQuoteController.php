<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BookingQuoteController extends Controller
{
    public function show(string $token): View
    {
        $bookingRequest = $this->bookingRequest($token);

        return view('booking-quotes.show', compact('bookingRequest'));
    }

    public function accept(string $token): RedirectResponse
    {
        $bookingRequest = $this->bookingRequest($token);

        abort_if($bookingRequest->quote_accepted_at, 409, 'Deze offerte is al akkoord gegeven.');
        abort_if($bookingRequest->quote_valid_until?->isPast(), 410, 'Deze offerte is verlopen.');

        $bookingRequest->update([
            'status' => BookingStatus::Confirmed,
            'quote_accepted_at' => now(),
            'agreement_accepted_at' => now(),
            'agreement_version' => $bookingRequest->quote_terms_version,
            'payment_status' => $bookingRequest->deposit_cents ? 'deposit_due' : 'not_required',
        ]);

        return redirect()
            ->route('booking-quotes.show', $bookingRequest->quote_acceptance_token)
            ->with('status', 'Bedankt, de offerte is akkoord gegeven en de boeking is bevestigd.');
    }

    private function bookingRequest(string $token): BookingRequest
    {
        return BookingRequest::query()
            ->where('quote_acceptance_token', $token)
            ->whereNotNull('quote_sent_at')
            ->firstOrFail();
    }
}
