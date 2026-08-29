<?php

namespace App\Actions;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Services\BookingWorkflowNotificationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SubmitReview
{
    public function __construct(private readonly BookingWorkflowNotificationService $notifications) {}

    /**
     * @param  array{rating: int, title?: string|null, body: string}  $data
     */
    public function handle(Review $review, array $data, Request $request): Review
    {
        if ($review->token_expires_at?->isPast()) {
            throw new HttpException(410);
        }

        if ($review->isSubmitted()) {
            throw new HttpException(409);
        }

        if (
            Review::query()
                ->where('booking_request_id', $review->booking_request_id)
                ->whereNotNull('submitted_at')
                ->whereKeyNot($review->id)
                ->exists()
        ) {
            throw new HttpException(409);
        }

        $review->fill($data);
        $review->forceFill([
            'status' => ReviewStatus::Pending,
            'submitted_at' => now(),
            'published_at' => null,
            'submission_ip' => $request->ip(),
            'submission_user_agent' => $request->userAgent(),
        ])->save();

        $this->notifications->notifyReviewSubmitted($review->refresh());

        return $review;
    }
}
