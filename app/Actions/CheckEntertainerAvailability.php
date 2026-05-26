<?php

namespace App\Actions;

use App\Enums\AvailabilityStatus;
use App\Models\Entertainer;
use Illuminate\Support\Carbon;

class CheckEntertainerAvailability
{
    public function handle(Entertainer $entertainer, string $date, string $startTime, string $endTime): bool
    {
        $start = Carbon::parse($startTime)->format('H:i:s');
        $end = Carbon::parse($endTime)->format('H:i:s');

        return $entertainer->availabilities()
            ->whereDate('date', $date)
            ->where('status', AvailabilityStatus::Available)
            ->whereTime('start_time', '<=', $start)
            ->whereTime('end_time', '>=', $end)
            ->exists();
    }
}
