<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\LegalDocumentType;
use App\Models\BookingRequest;
use App\Services\LegalDocumentRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class CreateBookingQuote
{
    public function __construct(private readonly LegalDocumentRepository $legalDocuments)
    {
    }

    public function handle(BookingRequest $bookingRequest, float $travelDistanceKm = 0.0, int $validDays = 14): BookingRequest
    {
        $rate = $bookingRequest->entertainer
            ?->rates()
            ->where('customer_type', $bookingRequest->customer_type)
            ->first();

        if (! $rate) {
            throw new RuntimeException('Er is geen passend tarief voor dit klanttype.');
        }

        $durationHours = $this->durationHours($bookingRequest);
        $billableHours = max($durationHours, (float) $rate->minimum_hours);
        $performanceCents = $rate->starting_rate_cents + (int) round($billableHours * $rate->hourly_rate_cents);
        $travelCents = (int) round(max(0, $travelDistanceKm) * $rate->travel_cost_cents_per_km);
        $totalCents = $performanceCents + $travelCents;
        $depositPercentage = (int) ($bookingRequest->entertainer?->deposit_percentage ?? 0);
        $terms = $this->legalDocuments->currentVersion(LegalDocumentType::Terms);

        $bookingRequest->update([
            'status' => BookingStatus::Option,
            'quote_performance_cents' => $performanceCents,
            'quote_travel_cents' => $travelCents,
            'quote_total_cents' => $totalCents,
            'deposit_cents' => $depositPercentage > 0 ? (int) round($totalCents * ($depositPercentage / 100)) : null,
            'payment_status' => 'open',
            'payment_due_at' => now()->addDays($validDays)->endOfDay(),
            'quote_travel_distance_km' => max(0, $travelDistanceKm),
            'quote_valid_until' => now()->addDays($validDays)->endOfDay(),
            'quote_sent_at' => now(),
            'quote_accepted_at' => null,
            'quote_acceptance_token' => $bookingRequest->quote_acceptance_token ?: Str::random(48),
            'quote_terms_version' => $terms?->version_label,
            'quote_terms_body' => $terms?->body,
        ]);

        return $bookingRequest->refresh();
    }

    private function durationHours(BookingRequest $bookingRequest): float
    {
        $start = Carbon::parse($bookingRequest->event_date->toDateString().' '.$bookingRequest->start_time->format('H:i'));
        $end = Carbon::parse($bookingRequest->event_date->toDateString().' '.$bookingRequest->end_time->format('H:i'));

        return max(0.5, $start->diffInMinutes($end) / 60);
    }
}
