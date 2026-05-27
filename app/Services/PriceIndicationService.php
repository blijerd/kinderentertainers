<?php

namespace App\Services;

use App\Enums\CustomerType;
use App\Models\Entertainer;
use App\Models\Rate;
use App\Models\Skill;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class PriceIndicationService
{
    private const WEEKEND_OR_HOLIDAY_SURCHARGE_PERCENT = 15;

    private const OUT_OF_REGION_SURCHARGE_CENTS = 2500;

    private const INCLUDED_ROUND_TRIP_TRAVEL_MINUTES = 60;

    private const EXTRA_CHILDREN_THRESHOLD = 20;

    private const EXTRA_CHILDREN_GROUP_SIZE = 15;

    private const EXTRA_CHILDREN_GROUP_CENTS = 2500;

    /**
     * @param  array<string, mixed>  $data
     * @return array{min_cents: int, max_cents: int, currency: string, breakdown: array<string, mixed>}|null
     */
    public function estimate(array $data, ?Entertainer $entertainer = null): ?array
    {
        if (blank($data['skill_id'] ?? null) || blank($data['customer_type'] ?? null) || blank($data['event_date'] ?? null) || blank($data['start_time'] ?? null) || blank($data['end_time'] ?? null)) {
            return null;
        }

        $skill = Skill::query()->whereKey($data['skill_id'])->where('active', true)->first();

        if (! $skill) {
            return null;
        }

        $customerType = $data['customer_type'] instanceof CustomerType
            ? $data['customer_type']
            : CustomerType::tryFrom((string) $data['customer_type']);

        if (! $customerType) {
            return null;
        }

        if (! $entertainer && filled($data['entertainer_id'] ?? null)) {
            $entertainer = Entertainer::query()->whereKey($data['entertainer_id'])->where('active', true)->first();
        }

        $date = CarbonImmutable::parse($data['event_date']);
        $start = CarbonImmutable::parse($date->toDateString().' '.$data['start_time']);
        $end = CarbonImmutable::parse($date->toDateString().' '.$data['end_time']);

        if ($end->lessThanOrEqualTo($start)) {
            return null;
        }

        $rates = $this->ratesFor($skill, $customerType, $entertainer);

        if ($rates->isEmpty()) {
            return null;
        }

        $durationMinutes = $start->diffInMinutes($end);
        $eventRegion = filled($data['event_region'] ?? null) ? (string) $data['event_region'] : null;
        $travelTimeMinutes = filled($data['travel_time_minutes'] ?? null) ? max(0, (int) $data['travel_time_minutes']) : 0;
        $childrenCount = filled($data['children_count'] ?? null) ? max(0, (int) $data['children_count']) : 0;
        $isPeakDate = $this->isWeekendOrDutchHoliday($date);

        $estimates = $rates
            ->map(fn (Rate $rate): int => $this->estimateForRate($rate, $durationMinutes, $date, $eventRegion, $travelTimeMinutes, $childrenCount))
            ->values();

        return [
            'min_cents' => $estimates->min(),
            'max_cents' => $estimates->max(),
            'currency' => 'EUR',
            'breakdown' => [
                'skill' => $skill->name,
                'customer_type' => $customerType->value,
                'duration_minutes' => $durationMinutes,
                'event_region' => $eventRegion,
                'travel_time_minutes_one_way' => $travelTimeMinutes,
                'children_count' => $childrenCount ?: null,
                'weekend_or_holiday' => $isPeakDate,
                'rules' => [
                    'weekend_or_holiday_surcharge_percent' => self::WEEKEND_OR_HOLIDAY_SURCHARGE_PERCENT,
                    'out_of_region_surcharge_cents' => self::OUT_OF_REGION_SURCHARGE_CENTS,
                    'included_round_trip_travel_minutes' => self::INCLUDED_ROUND_TRIP_TRAVEL_MINUTES,
                    'extra_children_threshold' => self::EXTRA_CHILDREN_THRESHOLD,
                    'extra_children_group_size' => self::EXTRA_CHILDREN_GROUP_SIZE,
                    'extra_children_group_cents' => self::EXTRA_CHILDREN_GROUP_CENTS,
                ],
            ],
        ];
    }

    /**
     * @return Collection<int, Rate>
     */
    private function ratesFor(Skill $skill, CustomerType $customerType, ?Entertainer $entertainer): Collection
    {
        $query = Rate::query()
            ->with('entertainer')
            ->where('customer_type', $customerType->value)
            ->whereHas('entertainer', fn ($query) => $query
                ->where('active', true)
                ->whereHas('skills', fn ($query) => $query->whereKey($skill->id)));

        if ($entertainer) {
            $query->where('entertainer_id', $entertainer->id);
        }

        return $query->get();
    }

    private function estimateForRate(Rate $rate, int $durationMinutes, CarbonImmutable $date, ?string $eventRegion, int $travelTimeMinutes, int $childrenCount): int
    {
        $billableHours = max((float) $rate->minimum_hours, $durationMinutes / 60);
        $subtotal = $rate->starting_rate_cents + (int) round($rate->hourly_rate_cents * $billableHours);

        if ($this->isWeekendOrDutchHoliday($date)) {
            $subtotal += (int) round($subtotal * (self::WEEKEND_OR_HOLIDAY_SURCHARGE_PERCENT / 100));
        }

        if ($eventRegion && $rate->entertainer && strcasecmp($eventRegion, (string) $rate->entertainer->region) !== 0) {
            $subtotal += self::OUT_OF_REGION_SURCHARGE_CENTS;
        }

        $billableTravelMinutes = max(0, ($travelTimeMinutes * 2) - self::INCLUDED_ROUND_TRIP_TRAVEL_MINUTES);
        $subtotal += (int) round(($rate->hourly_rate_cents / 60) * $billableTravelMinutes);

        if ($childrenCount > self::EXTRA_CHILDREN_THRESHOLD) {
            $groups = (int) ceil(($childrenCount - self::EXTRA_CHILDREN_THRESHOLD) / self::EXTRA_CHILDREN_GROUP_SIZE);
            $subtotal += $groups * self::EXTRA_CHILDREN_GROUP_CENTS;
        }

        return (int) (ceil($subtotal / 500) * 500);
    }

    private function isWeekendOrDutchHoliday(CarbonImmutable $date): bool
    {
        if ($date->isWeekend()) {
            return true;
        }

        $easter = CarbonImmutable::createFromTimestamp(easter_date($date->year))->startOfDay();
        $holidays = [
            $date->year.'-01-01',
            $date->year.'-04-27',
            $date->year.'-05-05',
            $date->year.'-12-25',
            $date->year.'-12-26',
            $easter->addDay()->toDateString(),
            $easter->addDays(39)->toDateString(),
            $easter->addDays(50)->toDateString(),
        ];

        return in_array($date->toDateString(), $holidays, true);
    }
}
