<?php

namespace App\Enums;

enum SensorStatus: string
{
    case Active = 'active';
    case Bypassed = 'bypassed';
    case Maintenance = 'maintenance';
    case Faulty = 'faulty';
    case Calibration = 'calibration';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actif',
            self::Bypassed => 'Contourné',
            self::Maintenance => 'En maintenance',
            self::Faulty => 'Défaillant',
            self::Calibration => 'En étalonnage',
        };
    }
}
