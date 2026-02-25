<?php

namespace App\Enums;

enum BypassCriticality: string
{
    case Process = 'process';
    case Securite = 'securite';

    public function label(): string
    {
        return match ($this) {
            self::Process => 'Process',
            self::Securite => 'Sécurité',
        };
    }
}
