<?php

namespace App\Actions;

use App\Enums\BookingRequestEventType;
use App\Enums\BookingRequestMatchStatus;
use App\Enums\BookingStatus;
use App\Models\BookingRequestMatch;
use App\Models\Entertainer;
use App\Models\User;

class RespondToBookingRequestMatch
{
    /**
     * @param  array{response: string, price_indication_euros?: mixed, response_message?: string|null}  $data
     */
    public function handle(BookingRequestMatch $match, Entertainer $entertainer, array $data, ?User $actor = null): BookingRequestMatch
    {
        if ($match->status === BookingRequestMatchStatus::Accepted) {
            return $match;
        }

        $response = $data['response'];
        $status = match ($response) {
            'accepted' => BookingRequestMatchStatus::Accepted,
            'available' => BookingRequestMatchStatus::Available,
            default => BookingRequestMatchStatus::Rejected,
        };

        $match->forceFill([
            'status' => $status,
            'price_indication_cents' => in_array($response, ['available', 'accepted'], true) && filled($data['price_indication_euros'] ?? null)
                ? (int) round(((float) $data['price_indication_euros']) * 100)
                : null,
            'response_message' => $data['response_message'] ?? null,
            'responded_at' => now(),
        ])->save();

        if (filled($data['response_message'] ?? null)) {
            $match->bookingRequest->events()->create([
                'type' => BookingRequestEventType::EntertainerResponse,
                'actor_type' => 'entertainer',
                'actor_name' => $entertainer->name,
                'body' => $data['response_message'],
                'visible_to_entertainer' => true,
                'visible_to_customer' => true,
                'user_id' => $actor?->id,
            ]);
        }

        if ($response === 'accepted') {
            $match->bookingRequest->forceFill([
                'status' => BookingStatus::Option,
            ])->save();
        }

        return $match->refresh();
    }
}
