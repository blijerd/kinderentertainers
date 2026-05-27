<?php

namespace App\Enums;

enum AccountingProvider: string
{
    case Manual = 'manual';
    case Moneybird = 'moneybird';
    case Exact = 'exact';
    case EBoekhouden = 'e_boekhouden';
    case SnelStart = 'snelstart';
    case Twinfield = 'twinfield';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Handmatig factureren',
            self::Moneybird => 'Moneybird',
            self::Exact => 'Exact Online',
            self::EBoekhouden => 'e-Boekhouden.nl',
            self::SnelStart => 'SnelStart',
            self::Twinfield => 'Twinfield',
            self::Other => 'Ander boekhoudpakket',
        };
    }
}
