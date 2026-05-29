<?php

use App\Actions\CreatePendingReviewForBookingRequest;
use App\Enums\BookingRequestEventType;
use App\Enums\BookingStatus;
use App\Mail\ReviewRequestMail;
use App\Models\BookingRequest;
use App\Models\EntertainerIntegration;
use App\Services\IntegrationHealthService;
use App\Services\Integrations\BookingNotificationService;
use App\Services\Integrations\CalendarIntegrationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
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
        ->whereDoesntHave('review', fn ($query) => $query
            ->whereNotNull('link_sent_at')
            ->orWhereNotNull('submitted_at'))
        ->chunkById(100, function ($bookingRequests) use ($createPendingReview, &$sent): void {
            foreach ($bookingRequests as $bookingRequest) {
                $review = $createPendingReview->handle($bookingRequest);

                if ($review === null || $review->link_sent_at !== null || $review->submitted_at !== null) {
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

Artisan::command('bookings:send-reminders', function (BookingNotificationService $notifications): int {
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
        ->chunkById(100, function ($bookingRequests) use ($notifications, &$sent): void {
            foreach ($bookingRequests as $bookingRequest) {
                $flags = $bookingRequest->reminder_flags ?? [];
                $key = $bookingRequest->status === BookingStatus::Confirmed ? 'event_tomorrow' : 'quote_expiring';

                if (($flags[$key] ?? false) === true) {
                    continue;
                }

                $bookingRequest->events()->create([
                    'type' => BookingRequestEventType::System,
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
                $notifications->sendReminder(
                    $bookingRequest,
                    $key === 'event_tomorrow' ? 'Reminder: boeking morgen' : 'Reminder: offerte verloopt bijna',
                    $key === 'event_tomorrow'
                        ? 'De boeking voor '.$bookingRequest->name.' is morgen om '.$bookingRequest->start_time->format('H:i').'.'
                        : 'De offerte voor '.$bookingRequest->name.' verloopt bijna. Controleer of er opvolging nodig is.',
                );
                $sent++;
            }
        });

    $this->info("{$sent} reminder(s) klaargezet.");

    return 0;
})->purpose('Create reminder events for expiring quotes and upcoming bookings');

Schedule::command('bookings:send-reminders')->hourly();

Artisan::command('integrations:check', function (IntegrationHealthService $health): int {
    $checked = 0;

    EntertainerIntegration::query()
        ->where('enabled', true)
        ->chunkById(100, function ($integrations) use ($health, &$checked): void {
            foreach ($integrations as $integration) {
                $result = $health->check($integration);
                $integration->update([
                    'last_checked_at' => now(),
                    'last_check_status' => $result['status'],
                    'last_check_message' => $result['message'],
                ]);
                $checked++;
            }
        });

    $this->info("{$checked} integratie(s) gecontroleerd.");

    return 0;
})->purpose('Validate configured entertainer integrations');

Artisan::command('calendar:sync-bookings', function (CalendarIntegrationService $calendar): int {
    $synced = 0;

    BookingRequest::query()
        ->where(function ($query): void {
            $query
                ->where(function ($subQuery): void {
                    $subQuery
                        ->where('status', BookingStatus::Confirmed->value)
                        ->where(function ($syncQuery): void {
                            $syncQuery
                                ->whereNull('calendar_synced_at')
                                ->orWhereColumn('updated_at', '>', 'calendar_synced_at');
                        });
                })
                ->orWhere(function ($subQuery): void {
                    $subQuery
                        ->whereIn('status', [BookingStatus::Rejected->value, BookingStatus::Cancelled->value])
                        ->whereNotNull('calendar_external_id')
                        ->where('calendar_sync_status', 'not like', 'deleted_%');
                });
        })
        ->with(['entertainer.integrations', 'skill'])
        ->chunkById(100, function ($bookingRequests) use ($calendar, &$synced): void {
            foreach ($bookingRequests as $bookingRequest) {
                $lock = Cache::lock('calendar-sync-booking-'.$bookingRequest->id, 300);

                if (! $lock->get()) {
                    continue;
                }

                try {
                    $calendar->syncBooking($bookingRequest);
                } catch (RuntimeException $exception) {
                    $bookingRequest->update([
                        'calendar_synced_at' => now(),
                        'calendar_sync_status' => 'sync_failed: '.$exception->getMessage(),
                    ]);
                } finally {
                    $lock->release();
                }

                $synced++;
            }
        });

    $this->info("{$synced} boeking(en) gemarkeerd voor agendasync.");

    return 0;
})->purpose('Prepare confirmed bookings for entertainer calendar sync');

Schedule::command('integrations:check')->dailyAt('08:00');
Schedule::command('calendar:sync-bookings')->everyFifteenMinutes();
