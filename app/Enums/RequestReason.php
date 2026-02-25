<?php

namespace App\Enums;

enum RequestReason: string
{
    case PreventiveMaintenance = 'preventive_maintenance';
    case CorrectiveMaintenance = 'corrective_maintenance';
    case Calibration = 'calibration';
    case Testing = 'testing';
    case EmergencyRepair = 'emergency_repair';
    case SystemUpgrade = 'system_upgrade';
    case Investigation = 'investigation';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PreventiveMaintenance => 'Maintenance préventive',
            self::CorrectiveMaintenance => 'Maintenance corrective',
            self::Calibration => 'Étalonnage',
            self::Testing => 'Tests',
            self::EmergencyRepair => 'Réparation d\'urgence',
            self::SystemUpgrade => 'Mise à niveau système',
            self::Investigation => 'Investigation',
            self::Other => 'Autre',
        };
    }
}
