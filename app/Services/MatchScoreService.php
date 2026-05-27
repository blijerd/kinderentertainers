<?php

namespace App\Services;

use App\Models\BookingRequest;
use App\Models\Entertainer;

class MatchScoreService
{
    public function __construct(private readonly RegionDistanceEstimator $distanceEstimator)
    {
    }

    /**
     * @return array{score: int, distance_km: ?float, travel_minutes: ?int, breakdown: array<string, int|string|null>}
     */
    public function score(Entertainer $entertainer, BookingRequest $bookingRequest): array
    {
        $distanceKm = $this->distanceEstimator->distanceKm(
            $entertainer->city,
            $bookingRequest->city,
            $entertainer->region,
            $bookingRequest->event_region,
        );
        $travelMinutes = $this->distanceEstimator->travelMinutes($distanceKm);

        $breakdown = [
            'skill' => 30,
            'availability' => 25,
            'distance' => $this->distanceScore($entertainer, $distanceKm),
            'reviews' => $this->reviewScore($entertainer),
            'response' => $this->responseScore($entertainer),
            'featured' => $entertainer->featured ? 5 : 0,
        ];

        return [
            'score' => min(100, array_sum($breakdown)),
            'distance_km' => $distanceKm,
            'travel_minutes' => $travelMinutes,
            'breakdown' => $breakdown + [
                'working_radius_km' => $entertainer->working_radius_km,
            ],
        ];
    }

    public function isInsideWorkingArea(Entertainer $entertainer, ?float $distanceKm): bool
    {
        return $distanceKm === null || $distanceKm <= $entertainer->working_radius_km;
    }

    private function distanceScore(Entertainer $entertainer, ?float $distanceKm): int
    {
        if ($distanceKm === null) {
            return 8;
        }

        if ($distanceKm > $entertainer->working_radius_km) {
            return 0;
        }

        $ratio = $distanceKm / max(1, $entertainer->working_radius_km);

        return (int) max(5, round(20 - ($ratio * 15)));
    }

    private function reviewScore(Entertainer $entertainer): int
    {
        if ($entertainer->reviews_count < 1 || $entertainer->rating === null) {
            return 3;
        }

        return (int) min(15, round(((float) $entertainer->rating / 5) * 10) + min(5, $entertainer->reviews_count));
    }

    private function responseScore(Entertainer $entertainer): int
    {
        if ($entertainer->average_response_minutes === null) {
            return 3;
        }

        return match (true) {
            $entertainer->average_response_minutes <= 60 => 5,
            $entertainer->average_response_minutes <= 240 => 4,
            $entertainer->average_response_minutes <= 1440 => 2,
            default => 0,
        };
    }
}
