<?php

namespace App\Enums;

enum CustomerType: string
{
    case Consumer = 'consument';
    case Business = 'b2b';

    public function label(): string
    {
        return match ($this) {
            self::Consumer => 'Consument',
            self::Business => 'Zakelijk',
        };
    }
}
