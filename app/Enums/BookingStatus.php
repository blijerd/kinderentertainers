<?php

namespace App\Enums;

enum BookingStatus: string
{
    case New = 'nieuw';
    case InProgress = 'in_behandeling';
    case Option = 'optie';
    case Confirmed = 'bevestigd';
    case Rejected = 'afgewezen';
    case Cancelled = 'geannuleerd';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nieuw',
            self::InProgress => 'In behandeling',
            self::Option => 'Optie',
            self::Confirmed => 'Bevestigd',
            self::Rejected => 'Afgewezen',
            self::Cancelled => 'Geannuleerd',
        };
    }
}
