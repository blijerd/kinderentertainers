<?php

namespace App\Actions;

use App\Enums\BookingRequestMatchStatus;
use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Models\BookingRequestMatch;
use App\Models\Skill;
use App\Services\BookingWorkflowNotificationService;
use App\Services\MatchScoreService;
use App\Services\PriceIndicationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateBookingRequest
{
    public function __construct(
        private readonly FindAvailableEntertainersForRequest $findAvailableEntertainers,
        private readonly PriceIndicationService $priceIndicationService,
        private readonly MatchScoreService $matchScoreService,
        private readonly BookingWorkflowNotificationService $notifications,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): BookingRequest
    {
        $data['status'] ??= BookingStatus::New;
        $data['customer_selection_token'] ??= Str::random(40);
        $data['customer_selection_expires_at'] ??= now()->addDays(14);
        $priceIndication = $this->priceIndicationService->estimate($data);

        if ($priceIndication) {
            $data['price_indication_min_cents'] = $priceIndication['min_cents'];
            $data['price_indication_max_cents'] = $priceIndication['max_cents'];
            $data['price_indication_currency'] = $priceIndication['currency'];
            $data['price_indication_breakdown'] = $priceIndication['breakdown'];
        }

        return DB::transaction(function () use ($data): BookingRequest {
            $status = $data['status'] ?? BookingStatus::New;
            unset($data['status']);

            $bookingRequest = new BookingRequest;
            $bookingRequest->fill($data);
            $bookingRequest->forceFill(['status' => $status])->save();

            if ($bookingRequest->isGeneral()) {
                $skill = Skill::findOrFail($bookingRequest->skill_id);
                $matches = $this->findAvailableEntertainers->handle(
                    $skill,
                    $bookingRequest->event_date->toDateString(),
                    $bookingRequest->start_time->format('H:i'),
                    $bookingRequest->end_time->format('H:i'),
                    $bookingRequest->event_region,
                );

                $matches
                    ->map(function ($entertainer) use ($bookingRequest): array {
                        $score = $this->matchScoreService->score($entertainer, $bookingRequest);

                        return [$entertainer, $score];
                    })
                    ->filter(fn (array $match): bool => $this->matchScoreService->isInsideWorkingArea($match[0], $match[1]['distance_km']))
                    ->sortByDesc(fn (array $match): int => $match[1]['score'])
                    ->each(function (array $match) use ($bookingRequest): void {
                        $bookingRequestMatch = new BookingRequestMatch;
                        $bookingRequestMatch->fill([
                            'booking_request_id' => $bookingRequest->id,
                            'entertainer_id' => $match[0]->id,
                            'match_score' => $match[1]['score'],
                            'distance_km' => $match[1]['distance_km'],
                            'travel_minutes' => $match[1]['travel_minutes'],
                            'score_breakdown' => $match[1]['breakdown'],
                            'matched_at' => now(),
                        ]);
                        $bookingRequestMatch->forceFill([
                            'status' => BookingRequestMatchStatus::Available,
                        ])->save();

                        $this->notifications->notifyNewMatch($bookingRequestMatch);
                    });
            }

            return $bookingRequest;
        });
    }
}
