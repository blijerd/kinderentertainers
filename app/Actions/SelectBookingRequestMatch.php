<?php

namespace App\Actions;

use App\Enums\BookingRequestMatchStatus;
use App\Enums\BookingStatus;
use App\Models\BookingRequestMatch;
use App\Services\BookingWorkflowNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SelectBookingRequestMatch
{
    public function __construct(private readonly BookingWorkflowNotificationService $notifications) {}

    public function handle(BookingRequestMatch $match): BookingRequestMatch
    {
        return DB::transaction(function () use ($match): BookingRequestMatch {
            $match = BookingRequestMatch::query()
                ->with('bookingRequest')
                ->lockForUpdate()
                ->findOrFail($match->id);
            $bookingRequest = $match->bookingRequest;

            if (
                $bookingRequest->entertainer_id !== null
                || ! $match->responded_at
                || in_array($match->status, [BookingRequestMatchStatus::Rejected, BookingRequestMatchStatus::Expired], true)
            ) {
                throw ValidationException::withMessages([
                    'match' => 'Deze match kan nog niet gekozen worden.',
                ]);
            }

            $expiredMatches = $bookingRequest->matches()
                ->with('entertainer.user')
                ->whereKeyNot($match->id)
                ->where('status', '!=', BookingRequestMatchStatus::Rejected->value)
                ->get();

            $bookingRequest->matches()
                ->whereKey($expiredMatches->pluck('id'))
                ->update([
                    'status' => BookingRequestMatchStatus::Expired->value,
                    'selected_at' => null,
                ]);

            $match->forceFill([
                'status' => BookingRequestMatchStatus::Accepted,
                'selected_at' => now(),
            ])->save();

            $bookingRequest->forceFill([
                'entertainer_id' => $match->entertainer_id,
                'status' => BookingStatus::Option,
            ])->save();

            $match = $match->refresh();

            $this->notifications->notifyMatchSelected($match);
            $expiredMatches->each(fn (BookingRequestMatch $expiredMatch) => $this->notifications->notifyMatchExpired($expiredMatch));
            $this->notifications->notifyCustomerBookingUpdated(
                $bookingRequest->refresh(),
                'Je keuze is opgeslagen',
                'Je hebt '.$match->entertainer?->name.' gekozen voor je aanvraag. De entertainer kan nu de offerte of verdere afstemming voorbereiden.',
            );

            return $match;
        });
    }
}
