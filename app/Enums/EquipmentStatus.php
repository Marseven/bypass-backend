<?php

namespace App\Enums;

enum EquipmentStatus: string
{
    case Operational = 'operational';
    case Maintenance = 'maintenance';
    case Down = 'down';
    case Standby = 'standby';

    public function label(): string
    {
        return match ($this) {
            self::Operational => 'Opérationnel',
            self::Maintenance => 'En maintenance',
            self::Down => 'Hors service',
            self::Standby => 'En veille',
        };
    }
}
