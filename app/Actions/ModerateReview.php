<?php

namespace App\Actions;

use App\Enums\ReviewStatus;
use App\Models\Review;

class ModerateReview
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Review $review, array $data): Review
    {
        $status = $data['status'] ?? $review->status;

        if (! $status instanceof ReviewStatus) {
            $status = is_string($status) && $status !== ''
                ? ReviewStatus::from($status)
                : ReviewStatus::Pending;
        }

        unset($data['status']);

        if ($status === ReviewStatus::Approved && blank($data['published_at'] ?? $review->published_at)) {
            $data['published_at'] = now();
        }

        if ($status !== ReviewStatus::Approved) {
            $data['published_at'] = null;
        }

        $review->fill($data);
        $review->forceFill(['status' => $status])->save();

        return $review->refresh();
    }
}
