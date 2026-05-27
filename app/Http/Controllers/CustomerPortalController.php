<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\BookingRequestEventType;
use App\Models\BookingRequest;
use App\Models\Entertainer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerPortalController extends Controller
{
    public function index(Request $request): View
    {
        $bookingRequests = BookingRequest::query()
            ->with(['entertainer', 'skill'])
            ->where(fn (Builder $query) => $this->scopeOwnedByCustomer($query, $request))
            ->latest('event_date')
            ->paginate(10);

        return view('customer-portal.index', [
            'bookingRequests' => $bookingRequests,
            'favoriteEntertainers' => $request->user()->favoriteEntertainers()->orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, BookingRequest $bookingRequest): View
    {
        $this->authorizeCustomer($request, $bookingRequest);

        return view('customer-portal.show', [
            'bookingRequest' => $bookingRequest->load([
                'entertainer',
                'skill',
                'matches.entertainer',
                'events' => fn ($query) => $query->where('visible_to_customer', true)->oldest(),
            ]),
        ]);
    }

    public function update(Request $request, BookingRequest $bookingRequest): RedirectResponse
    {
        $this->authorizeCustomer($request, $bookingRequest);

        $validated = $request->validate([
            'customer_type' => ['required', Rule::in(['consument', 'b2b'])],
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'required_if:customer_type,b2b', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'event_date' => ['required', 'date', 'after:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'address' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:16'],
            'city' => ['required', 'string', 'max:255'],
            'children_count' => ['nullable', 'integer', 'min:0', 'max:999'],
            'children_ages' => ['nullable', 'string', 'max:255'],
            'selected_package' => ['nullable', 'string', 'max:255'],
            'selected_extras' => ['nullable', 'array'],
            'selected_extras.*' => ['string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
        ]);

        $bookingRequest->update($validated);

        return back()->with('status', 'Je aanvraag is bijgewerkt.');
    }

    public function storeMessage(Request $request, BookingRequest $bookingRequest): RedirectResponse
    {
        $this->authorizeCustomer($request, $bookingRequest);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ], attributes: [
            'body' => 'bericht',
        ]);

        $bookingRequest->events()->create([
            'type' => BookingRequestEventType::CustomerMessage,
            'actor_type' => 'customer',
            'actor_name' => $request->user()->name,
            'body' => $validated['body'],
            'visible_to_entertainer' => true,
            'visible_to_customer' => true,
            'user_id' => $request->user()->id,
        ]);

        return back()->with('status', 'Bericht geplaatst.');
    }

    public function cancel(Request $request, BookingRequest $bookingRequest): RedirectResponse
    {
        $this->authorizeCustomer($request, $bookingRequest);
        abort_if(in_array($bookingRequest->status, [BookingStatus::Rejected, BookingStatus::Cancelled], true), Response::HTTP_UNPROCESSABLE_ENTITY);

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:5000'],
        ], attributes: [
            'cancellation_reason' => 'reden',
        ]);

        $bookingRequest->update([
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => 'customer',
            'cancellation_reason' => $validated['cancellation_reason'],
        ]);

        return back()->with('status', 'De aanvraag is geannuleerd.');
    }

    public function favorite(Request $request, Entertainer $entertainer): RedirectResponse
    {
        abort_unless($entertainer->active, Response::HTTP_NOT_FOUND);

        $request->user()->favoriteEntertainers()->syncWithoutDetaching([$entertainer->id]);

        return back()->with('status', "{$entertainer->name} is opgeslagen bij je favorieten.");
    }

    public function unfavorite(Request $request, Entertainer $entertainer): RedirectResponse
    {
        $request->user()->favoriteEntertainers()->detach($entertainer->id);

        return back()->with('status', "{$entertainer->name} is verwijderd uit je favorieten.");
    }

    public function acceptQuote(Request $request, BookingRequest $bookingRequest): RedirectResponse
    {
        $this->authorizeCustomer($request, $bookingRequest);

        abort_if(in_array($bookingRequest->status, [BookingStatus::Rejected, BookingStatus::Cancelled], true), Response::HTTP_UNPROCESSABLE_ENTITY);
        abort_unless($bookingRequest->quote_sent_at && $bookingRequest->quote_total_cents !== null, Response::HTTP_UNPROCESSABLE_ENTITY);
        abort_if($bookingRequest->quote_accepted_at, Response::HTTP_CONFLICT);
        abort_if($bookingRequest->quote_valid_until?->isPast(), Response::HTTP_GONE);

        $bookingRequest->update([
            'status' => BookingStatus::Confirmed,
            'quote_accepted_at' => now(),
            'agreement_accepted_at' => now(),
            'agreement_version' => $bookingRequest->quote_terms_version,
            'payment_status' => $bookingRequest->deposit_cents ? 'deposit_due' : 'not_required',
        ]);

        return back()->with('status', 'De offerte is geaccepteerd.');
    }

    public function download(Request $request, BookingRequest $bookingRequest): StreamedResponse
    {
        $this->authorizeCustomer($request, $bookingRequest);

        $bookingRequest->load(['entertainer', 'skill']);
        $filename = 'aanvraag-'.$bookingRequest->id.'.txt';

        return response()->streamDownload(function () use ($bookingRequest): void {
            echo $this->documentBody($bookingRequest);
        }, $filename, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function authorizeCustomer(Request $request, BookingRequest $bookingRequest): void
    {
        abort_unless($request->user()?->hasRole('admin') || $this->isOwnedByCustomer($request, $bookingRequest), Response::HTTP_FORBIDDEN);
    }

    private function scopeOwnedByCustomer(Builder $query, Request $request): Builder
    {
        return $query
            ->where('customer_id', $request->user()->id)
            ->orWhere('email', $request->user()->email);
    }

    private function isOwnedByCustomer(Request $request, BookingRequest $bookingRequest): bool
    {
        $user = $request->user();

        return $bookingRequest->customer_id === $user->id || strcasecmp($bookingRequest->email, $user->email) === 0;
    }

    private function documentBody(BookingRequest $bookingRequest): string
    {
        return implode(PHP_EOL, [
            'Aanvraag '.$bookingRequest->id,
            'Status: '.$bookingRequest->status->label(),
            'Naam: '.$bookingRequest->name,
            'E-mail: '.$bookingRequest->email,
            'Telefoon: '.$bookingRequest->phone,
            'Evenement: '.$bookingRequest->event_date->format('d-m-Y').' van '.$bookingRequest->start_time->format('H:i').' tot '.$bookingRequest->end_time->format('H:i'),
            'Adres: '.$bookingRequest->address.', '.$bookingRequest->postal_code.' '.$bookingRequest->city,
            'Entertainer: '.($bookingRequest->entertainer?->name ?? 'Nog te kiezen'),
            'Skill: '.($bookingRequest->skill?->name ?? implode(', ', $bookingRequest->desired_skills ?? [])),
            'Bericht: '.($bookingRequest->message ?: '-'),
            'Klantbericht: '.($bookingRequest->customer_message ?: '-'),
            'Offerte geaccepteerd: '.($bookingRequest->quote_accepted_at?->format('d-m-Y H:i') ?? 'Nee'),
            'Overeenkomstversie: '.($bookingRequest->agreement_version ?: '-'),
            'Aanbetaling: '.($bookingRequest->deposit_cents ? '€ '.number_format($bookingRequest->deposit_cents / 100, 2, ',', '.') : '-'),
            'Betaalstatus: '.$bookingRequest->payment_status,
            'Annulering: '.($bookingRequest->cancelled_at ? $bookingRequest->cancelled_at->format('d-m-Y H:i').' door '.$bookingRequest->cancelled_by : '-'),
            'Annuleringsreden: '.($bookingRequest->cancellation_reason ?: '-'),
        ]).PHP_EOL;
    }
}
