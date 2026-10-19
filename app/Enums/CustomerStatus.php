<?php

namespace App\Enums;

enum CustomerStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Inactive => 'Nonaktif',
            self::Suspended => 'Ditangguhkan',
        };
    }

    public function isBillable(): bool
    {
        return $this === self::Active;
    }
}
