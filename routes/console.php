<?php

use App\Actions\CreatePendingReviewForBookingRequest;
use App\Enums\BookingStatus;
use App\Mail\ReviewRequestMail;
use App\Models\BookingRequest;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reviews:send-links', function (CreatePendingReviewForBookingRequest $createPendingReview): int {
    $sent = 0;

    BookingRequest::query()
        ->with(['entertainer', 'matches.entertainer', 'review'])
        ->where('status', BookingStatus::Confirmed->value)
        ->whereDate('event_date', '<', today())
        ->whereDoesntHave('review', fn ($query) => $query->whereNotNull('link_sent_at'))
        ->chunkById(100, function ($bookingRequests) use ($createPendingReview, &$sent): void {
            foreach ($bookingRequests as $bookingRequest) {
                $review = $createPendingReview->handle($bookingRequest);

                if ($review === null || $review->link_sent_at !== null) {
                    continue;
                }

                Mail::to($review->customer_email)->send(new ReviewRequestMail($review));

                $review->update(['link_sent_at' => now()]);
                $sent++;
            }
        });

    $this->info("{$sent} review-link(s) verzonden.");

    return 0;
})->purpose('Send review links for completed confirmed bookings');

Schedule::command('reviews:send-links')->dailyAt('09:00');

Artisan::command('bookings:send-reminders', function (): int {
    $sent = 0;

    BookingRequest::query()
        ->whereNotIn('status', [BookingStatus::Rejected->value, BookingStatus::Cancelled->value])
        ->where(function ($query): void {
            $query
                ->where(fn ($subQuery) => $subQuery
                    ->whereNotNull('quote_sent_at')
                    ->whereNull('quote_accepted_at')
                    ->where('quote_valid_until', '<=', now()->addDays(2)))
                ->orWhere(fn ($subQuery) => $subQuery
                    ->where('status', BookingStatus::Confirmed->value)
                    ->whereDate('event_date', today()->addDay()));
        })
        ->chunkById(100, function ($bookingRequests) use (&$sent): void {
            foreach ($bookingRequests as $bookingRequest) {
                $flags = $bookingRequest->reminder_flags ?? [];
                $key = $bookingRequest->quote_accepted_at ? 'event_tomorrow' : 'quote_expiring';

                if (($flags[$key] ?? false) === true) {
                    continue;
                }

                $bookingRequest->events()->create([
                    'type' => \App\Enums\BookingRequestEventType::System,
                    'actor_type' => 'system',
                    'actor_name' => 'Platform',
                    'body' => $key === 'event_tomorrow'
                        ? 'Reminder aangemaakt: de boeking is morgen.'
                        : 'Reminder aangemaakt: de offerte verloopt bijna.',
                    'visible_to_entertainer' => true,
                    'visible_to_customer' => true,
                ]);

                $flags[$key] = true;
                $bookingRequest->update([
                    'last_reminder_sent_at' => now(),
                    'reminder_flags' => $flags,
                ]);
                $sent++;
            }
        });

    $this->info("{$sent} reminder(s) klaargezet.");

    return 0;
})->purpose('Create reminder events for expiring quotes and upcoming bookings');

Schedule::command('bookings:send-reminders')->hourly();
