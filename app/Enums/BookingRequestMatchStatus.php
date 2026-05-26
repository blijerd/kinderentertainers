<?php

namespace App\Enums;

enum BookingRequestMatchStatus: string
{
    case Available = 'beschikbaar';
    case OptionSent = 'optie_verstuurd';
    case Rejected = 'afgewezen';
    case Accepted = 'geaccepteerd';
    case Expired = 'verlopen';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Beschikbaar',
            self::OptionSent => 'Optie verstuurd',
            self::Rejected => 'Afgewezen',
            self::Accepted => 'Geaccepteerd',
            self::Expired => 'Verlopen',
        };
    }
}
