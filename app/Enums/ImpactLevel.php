<?php

namespace App\Enums;

enum ImpactLevel: string
{
    case VeryLow = 'very_low';
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case VeryHigh = 'very_high';

    public function label(): string
    {
        return match ($this) {
            self::VeryLow => 'Très faible',
            self::Low => 'Faible',
            self::Medium => 'Moyen',
            self::High => 'Élevé',
            self::VeryHigh => 'Très élevé',
        };
    }
}
