<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Services\Integrations\CalendarIntegrationService;
use Illuminate\Validation\ValidationException;

class TransitionBookingRequestStatus
{
    /**
     * @var list<BookingStatus>
     */
    private const ALLOWED = [
        BookingStatus::Option,
        BookingStatus::Confirmed,
        BookingStatus::Rejected,
        BookingStatus::Cancelled,
    ];

    public function __construct(private readonly CalendarIntegrationService $calendar) {}

    public function handle(
        BookingRequest $bookingRequest,
        BookingStatus $status,
        ?string $cancellationReason = null,
        string $cancelledBy = 'entertainer',
    ): BookingRequest {
        if (! in_array($status, self::ALLOWED, true)) {
            throw ValidationException::withMessages([
                'status' => 'Deze statuswijziging is niet toegestaan.',
            ]);
        }

        if (in_array($bookingRequest->status, [BookingStatus::Rejected, BookingStatus::Cancelled], true)
            && $status !== $bookingRequest->status) {
            throw ValidationException::withMessages([
                'status' => 'Een afgewezen of geannuleerde aanvraag kan niet meer worden gewijzigd.',
            ]);
        }

        $payload = ['status' => $status];

        if ($status === BookingStatus::Cancelled) {
            $payload += [
                'cancelled_at' => now(),
                'cancelled_by' => $cancelledBy,
                'cancellation_reason' => $cancellationReason,
            ];
        }

        $bookingRequest->forceFill($payload)->save();

        if ($status === BookingStatus::Cancelled) {
            try {
                $this->calendar->syncBooking($bookingRequest->refresh());
            } catch (\RuntimeException) {
                // Scheduled sync will retry and expose the failure in the admin signals.
            }
        }

        return $bookingRequest->refresh();
    }
}
