<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Success = 'success';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Success => 'Berhasil',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Success => 'bg-success-subtle text-success-emphasis',
            self::Cancelled => 'bg-danger-subtle text-danger-emphasis',
        };
    }
}
