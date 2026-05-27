<?php

namespace App\Actions;

use App\Enums\BookingRequestMatchStatus;
use App\Enums\BookingStatus;
use App\Models\BookingRequestMatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SelectBookingRequestMatch
{
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

            $bookingRequest->matches()
                ->whereKeyNot($match->id)
                ->where('status', '!=', BookingRequestMatchStatus::Rejected->value)
                ->update([
                    'status' => BookingRequestMatchStatus::Expired->value,
                    'selected_at' => null,
                ]);

            $match->update([
                'status' => BookingRequestMatchStatus::Accepted,
                'selected_at' => now(),
            ]);

            $bookingRequest->update([
                'entertainer_id' => $match->entertainer_id,
                'status' => BookingStatus::Option,
            ]);

            return $match->refresh();
        });
    }
}
