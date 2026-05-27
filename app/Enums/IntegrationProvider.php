<?php

namespace App\Enums;

enum IntegrationProvider: string
{
    case GoogleCalendar = 'google_calendar';
    case OutlookCalendar = 'outlook_calendar';
    case Pushover = 'pushover';
    case Mollie = 'mollie';
    case Moneybird = 'moneybird';
    case Postmark = 'postmark';

    public function label(): string
    {
        return match ($this) {
            self::GoogleCalendar => 'Google Calendar',
            self::OutlookCalendar => 'Outlook Calendar',
            self::Pushover => 'Pushover',
            self::Mollie => 'Mollie',
            self::Moneybird => 'Moneybird',
            self::Postmark => 'Postmark',
        };
    }
}
