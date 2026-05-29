<?php

namespace App\Enums;

enum IntegrationProvider: string
{
    case GoogleCalendar = 'google_calendar';
    case OutlookCalendar = 'outlook_calendar';
    case Pushover = 'pushover';
    case Mollie = 'mollie';
    case Stripe = 'stripe';
    case PayPal = 'paypal';
    case PayNl = 'pay_nl';
    case Rabobank = 'rabobank';
    case Moneybird = 'moneybird';
    case Exact = 'exact';
    case EBoekhouden = 'e_boekhouden';
    case SnelStart = 'snelstart';
    case Twinfield = 'twinfield';
    case Jortt = 'jortt';
    case Rompslomp = 'rompslomp';
    case Visma = 'visma';
    case Postmark = 'postmark';

    public function label(): string
    {
        return match ($this) {
            self::GoogleCalendar => 'Google Calendar',
            self::OutlookCalendar => 'Outlook Calendar',
            self::Pushover => 'Pushover',
            self::Mollie => 'Mollie',
            self::Stripe => 'Stripe',
            self::PayPal => 'PayPal',
            self::PayNl => 'Pay.nl',
            self::Rabobank => 'Rabo Smart Pay',
            self::Moneybird => 'Moneybird',
            self::Exact => 'Exact Online',
            self::EBoekhouden => 'e-Boekhouden.nl',
            self::SnelStart => 'SnelStart',
            self::Twinfield => 'Twinfield',
            self::Jortt => 'Jortt',
            self::Rompslomp => 'Rompslomp',
            self::Visma => 'Visma eAccounting',
            self::Postmark => 'Postmark',
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::Moneybird,
            self::Exact,
            self::EBoekhouden,
            self::SnelStart,
            self::Twinfield,
            self::Jortt,
            self::Rompslomp,
            self::Visma => 'accounting',

            self::Mollie,
            self::Stripe,
            self::PayPal,
            self::PayNl,
            self::Rabobank => 'payment',

            self::GoogleCalendar,
            self::OutlookCalendar => 'calendar',

            self::Pushover,
            self::Postmark => 'communication',
        };
    }
}
