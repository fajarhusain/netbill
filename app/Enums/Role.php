<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Collector = 'collector';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Collector => 'Petugas Penagih',
        };
    }
}
