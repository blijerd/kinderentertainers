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
    case Jortt = 'jortt';
    case Rompslomp = 'rompslomp';
    case Visma = 'visma';
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
            self::Jortt => 'Jortt',
            self::Rompslomp => 'Rompslomp',
            self::Visma => 'Visma eAccounting',
            self::Other => 'Ander boekhoudpakket',
        };
    }
}
