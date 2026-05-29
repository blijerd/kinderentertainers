<?php

namespace App\Services;

use App\Mail\PlainTextNotificationMail;
use App\Models\BookingRequest;
use App\Models\BookingRequestMatch;
use App\Models\Review;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class BookingWorkflowNotificationService
{
    public function notifyNewMatch(BookingRequestMatch $match): void
    {
        $match->loadMissing('bookingRequest.skill', 'entertainer.user');

        $email = $match->entertainer?->user?->email;

        if (! $email) {
            return;
        }

        Mail::to($email)->send(new PlainTextNotificationMail(
            'Nieuwe aanvraag via Kinderentertainers.nl',
            'Er staat een nieuwe aanvraag klaar voor '.$match->bookingRequest->event_date->format('d-m-Y').' in '.$match->bookingRequest->city.'. Reageer via je dashboard.',
        ));
    }

    public function notifyMatchSelected(BookingRequestMatch $match): void
    {
        $match->loadMissing('bookingRequest.skill', 'entertainer.user');

        $email = $match->entertainer?->user?->email;

        if (! $email) {
            return;
        }

        Mail::to($email)->send(new PlainTextNotificationMail(
            'Je bent gekozen voor een aanvraag',
            'De klant heeft jou gekozen voor de aanvraag op '.$match->bookingRequest->event_date->format('d-m-Y').'. Maak de offerte of stem de details af via je dashboard.',
        ));
    }

    public function notifyMatchExpired(BookingRequestMatch $match): void
    {
        $match->loadMissing('bookingRequest', 'entertainer.user');

        $email = $match->entertainer?->user?->email;

        if (! $email) {
            return;
        }

        Mail::to($email)->send(new PlainTextNotificationMail(
            'Aanvraag is niet meer beschikbaar',
            'De klant heeft een andere entertainer gekozen voor de aanvraag op '.$match->bookingRequest->event_date->format('d-m-Y').'.',
        ));
    }

    public function notifyCustomerBookingUpdated(BookingRequest $bookingRequest, string $subject, string $body): void
    {
        if (! $bookingRequest->email) {
            return;
        }

        Mail::to($bookingRequest->email)->send(new PlainTextNotificationMail($subject, $body));
    }

    public function notifyReviewSubmitted(Review $review): void
    {
        $review->loadMissing('entertainer.user');

        $recipients = collect();

        if ($review->entertainer?->user?->email) {
            $recipients->push($review->entertainer->user->email);
        }

        $adminRole = Role::query()->where('name', 'admin')->where('guard_name', 'web')->first();
        if ($adminRole) {
            $recipients = $recipients->merge($adminRole->users()->pluck('email'));
        }

        $recipients
            ->filter()
            ->unique()
            ->each(fn (string $email) => Mail::to($email)->send(new PlainTextNotificationMail(
                'Nieuwe review wacht op moderatie',
                'Er is een nieuwe review ingestuurd voor '.$review->entertainer?->name.'. Controleer en publiceer deze in het beheer.',
            )));
    }
}
