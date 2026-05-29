<?php

namespace App\Http\Controllers;

use App\Actions\AcceptBookingQuote;
use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BookingQuoteController extends Controller
{
    public function show(string $token): View
    {
        $bookingRequest = $this->bookingRequest($token);

        return view('booking-quotes.show', compact('bookingRequest'));
    }

    public function accept(Request $request, string $token, AcceptBookingQuote $acceptBookingQuote): RedirectResponse
    {
        $bookingRequest = $this->bookingRequest($token);

        abort_if(in_array($bookingRequest->status, [BookingStatus::Rejected, BookingStatus::Cancelled], true), Response::HTTP_UNPROCESSABLE_ENTITY);
        abort_unless($bookingRequest->quote_total_cents !== null, Response::HTTP_UNPROCESSABLE_ENTITY);
        abort_if($bookingRequest->quote_accepted_at, Response::HTTP_CONFLICT, 'Deze offerte is al akkoord gegeven.');
        abort_if($bookingRequest->quote_valid_until?->isPast(), Response::HTTP_GONE, 'Deze offerte is verlopen.');

        $validated = $request->validate([
            'acceptance_name' => ['nullable', 'string', 'max:255'],
        ]);

        $acceptBookingQuote->handle($bookingRequest, [
            'acceptance_name' => $validated['acceptance_name'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
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
