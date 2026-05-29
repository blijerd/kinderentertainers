<?php

namespace App\Actions;

use App\Enums\BookingRequestMatchStatus;
use App\Models\BookingRequest;
use App\Models\Entertainer;
use App\Models\Review;

class CreatePendingReviewForBookingRequest
{
    public function handle(BookingRequest $bookingRequest): ?Review
    {
        $entertainer = $this->resolveEntertainer($bookingRequest);

        if ($entertainer === null) {
            return null;
        }

        return Review::query()->firstOrCreate(
            ['booking_request_id' => $bookingRequest->id],
            [
                'entertainer_id' => $entertainer->id,
                'customer_name' => $bookingRequest->name,
                'customer_email' => $bookingRequest->email,
                'token_expires_at' => now()->addDays(45),
            ],
        );
    }

    private function resolveEntertainer(BookingRequest $bookingRequest): ?Entertainer
    {
        if ($bookingRequest->entertainer_id !== null) {
            return $bookingRequest->entertainer;
        }

        $match = $bookingRequest->matches()
            ->where(function ($query): void {
                $query
                    ->whereNotNull('selected_at')
                    ->orWhere('status', BookingRequestMatchStatus::Accepted);
            })
            ->latest('selected_at')
            ->first();

        return $match?->entertainer;
    }
}
