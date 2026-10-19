<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum Dibayar',
            self::Partial => 'Dibayar Sebagian',
            self::Paid => 'Lunas',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Unpaid => 'bg-danger-subtle text-danger-emphasis',
            self::Partial => 'bg-warning-subtle text-warning-emphasis',
            self::Paid => 'bg-success-subtle text-success-emphasis',
            self::Cancelled => 'bg-secondary-subtle text-secondary-emphasis',
        };
    }
}
