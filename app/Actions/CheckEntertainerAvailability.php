<?php

namespace App\Actions;

use App\Enums\AvailabilityStatus;
use App\Enums\BookingStatus;
use App\Models\Entertainer;
use Illuminate\Support\Carbon;

class CheckEntertainerAvailability
{
    public function handle(Entertainer $entertainer, string $date, string $startTime, string $endTime): bool
    {
        $start = Carbon::parse($startTime)->format('H:i:s');
        $end = Carbon::parse($endTime)->format('H:i:s');

        if ($end <= $start) {
            return false;
        }

        $hasAvailableBlock = $entertainer->availabilities()
            ->whereDate('date', $date)
            ->where('status', AvailabilityStatus::Available)
            ->whereTime('start_time', '<=', $start)
            ->whereTime('end_time', '>=', $end)
            ->exists();

        if (! $hasAvailableBlock) {
            return false;
        }

        $hasAvailabilityConflict = $entertainer->availabilities()
            ->whereDate('date', $date)
            ->whereIn('status', [
                AvailabilityStatus::Booked,
                AvailabilityStatus::Option,
                AvailabilityStatus::Unavailable,
            ])
            ->whereTime('start_time', '<', $end)
            ->whereTime('end_time', '>', $start)
            ->exists();

        if ($hasAvailabilityConflict) {
            return false;
        }

        return ! $entertainer->bookingRequests()
            ->whereDate('event_date', $date)
            ->whereIn('status', [
                BookingStatus::InProgress,
                BookingStatus::Option,
                BookingStatus::Confirmed,
            ])
            ->whereTime('start_time', '<', $end)
            ->whereTime('end_time', '>', $start)
            ->exists();
    }
}
