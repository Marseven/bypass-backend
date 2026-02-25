<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\Request;
use App\Models\Sensor;

class CodeGenerationService
{
    public function generateBypassCode(): string
    {
        $year = date('Y');
        $prefix = "BYP-{$year}-";

        $lastRequest = Request::where('request_code', 'like', $prefix . '%')
            ->orderBy('request_code', 'desc')
            ->first();

        $nextNumber = $lastRequest
            ? (int) substr($lastRequest->request_code, -4) + 1
            : 1;

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function generateEquipmentCode(string $equipmentName, string $zoneName): string
    {
        $sequence = Equipment::count() + 1;

        $consonants = strtoupper(preg_replace('/[aeiouAEIOU\s]/', '', $equipmentName));
        $consonants = substr($consonants, 0, 3) ?: 'EQP';

        $zonePrefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $zoneName), 0, 2)) ?: 'ZN';

        return sprintf('%s-%s-%03d', $consonants, $zonePrefix, $sequence);
    }

    public function generateSensorCode(string $sensorName, string $equipmentName, string $zoneName): string
    {
        $sequence = Sensor::count() + 1;

        $sensorPrefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $sensorName), 0, 2)) ?: 'SN';
        $equipPrefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $equipmentName), 0, 2)) ?: 'EQ';
        $zonePrefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $zoneName), 0, 2)) ?: 'ZN';

        return sprintf('%s-%s-%s-%03d', $sensorPrefix, $equipPrefix, $zonePrefix, $sequence);
    }
}
