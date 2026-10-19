<?php

namespace App\Enums;

enum VisitResult: string
{
    case Paid = 'paid';
    case NotHome = 'not_home';
    case RecallRequested = 'recall_requested';
    case CannotPay = 'cannot_pay';
    case AddressNotFound = 'address_not_found';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Paid => 'Berhasil menerima pembayaran',
            self::NotHome => 'Pelanggan tidak ada di rumah',
            self::RecallRequested => 'Pelanggan meminta kunjungan ulang',
            self::CannotPay => 'Pelanggan belum dapat membayar',
            self::AddressNotFound => 'Alamat tidak ditemukan',
            self::Other => 'Kendala lain',
        };
    }

    public function isPayment(): bool
    {
        return $this === self::Paid;
    }
}
