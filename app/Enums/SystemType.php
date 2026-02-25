<?php

namespace App\Enums;

enum SystemType: string
{
    case Process = 'process';
    case Securite = 'securite';
    case FeuGaz = 'feu_gaz';

    public function label(): string
    {
        return match ($this) {
            self::Process => 'Process',
            self::Securite => 'Sécurité',
            self::FeuGaz => 'Feu & Gaz',
        };
    }
}
