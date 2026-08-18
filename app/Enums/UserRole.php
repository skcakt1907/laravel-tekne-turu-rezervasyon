<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Owner = 'owner';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Yönetici',
            self::Owner => 'Yat Sahibi',
            self::Customer => 'Müşteri',
        };
    }
}
