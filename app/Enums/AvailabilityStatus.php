<?php

namespace App\Enums;

enum AvailabilityStatus: string
{
    case Available = 'beschikbaar';
    case Option = 'optie';
    case Booked = 'bezet';
    case Unavailable = 'niet_beschikbaar';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Beschikbaar',
            self::Option => 'Optie',
            self::Booked => 'Bezet',
            self::Unavailable => 'Niet beschikbaar',
        };
    }
}
