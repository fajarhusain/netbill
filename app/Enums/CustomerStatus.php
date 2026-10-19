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

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'bg-success-subtle text-success-emphasis',
            self::Suspended => 'bg-warning-subtle text-warning-emphasis',
            self::Inactive => 'bg-secondary-subtle text-secondary-emphasis',
        };
    }

    public function isBillable(): bool
    {
        return $this === self::Active;
    }
}
