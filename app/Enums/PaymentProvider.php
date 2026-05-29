<?php

namespace App\Enums;

enum PaymentProvider: string
{
    case Manual = 'manual';
    case Mollie = 'mollie';
    case Stripe = 'stripe';
    case PayPal = 'paypal';
    case PayNl = 'pay_nl';
    case Rabobank = 'rabobank';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Handmatig betaalverzoek',
            self::Mollie => 'Mollie',
            self::Stripe => 'Stripe',
            self::PayPal => 'PayPal',
            self::PayNl => 'Pay.nl',
            self::Rabobank => 'Rabo Smart Pay',
            self::Other => 'Andere betaalprovider',
        };
    }
}
