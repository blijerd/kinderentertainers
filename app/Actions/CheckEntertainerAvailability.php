<?php

namespace App\Actions;

use App\Enums\AvailabilityStatus;
use App\Enums\BookingRequestMatchStatus;
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
            $hasAvailableBlock = $this->hasMatchingRule($entertainer, $date, $start, $end, [AvailabilityStatus::Available], true);
        }

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

        if ($this->hasMatchingRule($entertainer, $date, $start, $end, [
            AvailabilityStatus::Booked,
            AvailabilityStatus::Option,
            AvailabilityStatus::Unavailable,
        ])) {
            return false;
        }

        $hasSpecificBookingConflict = $entertainer->bookingRequests()
            ->whereDate('event_date', $date)
            ->whereIn('status', [
                BookingStatus::InProgress,
                BookingStatus::Option,
                BookingStatus::Confirmed,
            ])
            ->whereTime('start_time', '<', $end)
            ->whereTime('end_time', '>', $start)
            ->exists();

        if ($hasSpecificBookingConflict) {
            return false;
        }

        return ! $entertainer->bookingRequestMatches()
            ->whereNotIn('status', [
                BookingRequestMatchStatus::Rejected,
                BookingRequestMatchStatus::Expired,
            ])
            ->whereHas('bookingRequest', fn ($query) => $query
                ->whereDate('event_date', $date)
                ->whereIn('status', [
                    BookingStatus::InProgress,
                    BookingStatus::Option,
                    BookingStatus::Confirmed,
                ])
                ->whereTime('start_time', '<', $end)
                ->whereTime('end_time', '>', $start))
            ->exists();
    }

    /**
     * @param  array<int, AvailabilityStatus>  $statuses
     */
    private function hasMatchingRule(Entertainer $entertainer, string $date, string $start, string $end, array $statuses, bool $mustCover = false): bool
    {
        $query = $entertainer->availabilityRules()
            ->whereIn('status', $statuses)
            ->whereDate('starts_on', '<=', $date)
            ->where(function ($query) use ($date): void {
                $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $date);
            });

        if ($mustCover) {
            $query->whereTime('start_time', '<=', $start)
                ->whereTime('end_time', '>=', $end);
        } else {
            $query->whereTime('start_time', '<', $end)
                ->whereTime('end_time', '>', $start);
        }

        return $query->get()->contains(fn ($rule): bool => $rule->appliesTo($date));
    }
}
