<?php

namespace App\Enums;

enum BypassType: string
{
    case Maintenance = 'maintenance';
    case Operationnel = 'operationnel';
    case Permissif = 'permissif';

    public function label(): string
    {
        return match ($this) {
            self::Maintenance => 'Maintenance',
            self::Operationnel => 'Opérationnel',
            self::Permissif => 'Permissif',
        };
    }
}
