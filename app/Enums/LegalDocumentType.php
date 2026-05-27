<?php

namespace App\Enums;

enum LegalDocumentType: string
{
    case Terms = 'terms';
    case Privacy = 'privacy';
    case Cookie = 'cookie';

    public function label(): string
    {
        return match ($this) {
            self::Terms => 'Algemene voorwaarden',
            self::Privacy => 'Privacyverklaring',
            self::Cookie => 'Cookieverklaring',
        };
    }

    public function routeName(): string
    {
        return match ($this) {
            self::Terms => 'legal.terms',
            self::Privacy => 'legal.privacy',
            self::Cookie => 'legal.cookies',
        };
    }
}
