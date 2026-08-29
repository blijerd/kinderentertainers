<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CancelBookingRequest
{
    public function __construct(private readonly TransitionBookingRequestStatus $transition) {}

    public function handle(BookingRequest $bookingRequest, string $reason, string $cancelledBy = 'customer'): BookingRequest
    {
        if (in_array($bookingRequest->status, [BookingStatus::Rejected, BookingStatus::Cancelled], true)) {
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->transition->handle(
            $bookingRequest,
            BookingStatus::Cancelled,
            $reason,
            $cancelledBy,
        );
    }
}
