<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case Pending = 'in_afwachting';
    case Approved = 'goedgekeurd';
    case Rejected = 'afgewezen';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'In afwachting',
            self::Approved => 'Goedgekeurd',
            self::Rejected => 'Afgewezen',
        };
    }
}
