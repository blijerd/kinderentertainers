<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\BookingRequestMatchStatus;
use App\Models\BookingRequestMatch;
use App\Models\BookingRequest;
use App\Models\Skill;
use Illuminate\Support\Facades\DB;

class CreateBookingRequest
{
    public function __construct(private readonly FindAvailableEntertainersForRequest $findAvailableEntertainers)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): BookingRequest
    {
        $data['status'] ??= BookingStatus::New;

        return DB::transaction(function () use ($data): BookingRequest {
            $bookingRequest = BookingRequest::create($data);

            if ($bookingRequest->isGeneral()) {
                $skill = Skill::findOrFail($bookingRequest->skill_id);
                $matches = $this->findAvailableEntertainers->handle(
                    $skill,
                    $bookingRequest->event_date->toDateString(),
                    $bookingRequest->start_time->format('H:i'),
                    $bookingRequest->end_time->format('H:i'),
                );

                $matches->each(fn ($entertainer) => BookingRequestMatch::create([
                    'booking_request_id' => $bookingRequest->id,
                    'entertainer_id' => $entertainer->id,
                    'status' => BookingRequestMatchStatus::Available,
                    'matched_at' => now(),
                ]));
            }

            return $bookingRequest;
        });
    }
}
