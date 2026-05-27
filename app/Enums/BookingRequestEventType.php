<?php

namespace App\Enums;

enum BookingRequestEventType: string
{
    case CustomerMessage = 'customer_message';
    case InternalNote = 'internal_note';
    case StatusChange = 'status_change';
    case EntertainerResponse = 'entertainer_response';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::CustomerMessage => 'Klantbericht',
            self::InternalNote => 'Interne notitie',
            self::StatusChange => 'Statuswijziging',
            self::EntertainerResponse => 'Entertainer-reactie',
            self::System => 'Systeem',
        };
    }
}
