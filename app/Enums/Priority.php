<?php

namespace App\Enums;

enum Priority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Critical = 'critical';
    case Emergency = 'emergency';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Faible',
            self::Normal => 'Normale',
            self::High => 'Élevée',
            self::Critical => 'Critique',
            self::Emergency => 'Urgence',
        };
    }

    public function requiresDualValidation(): bool
    {
        return in_array($this, [self::Critical, self::Emergency]);
    }

    public function validationRole(): string
    {
        return $this->requiresDualValidation() ? 'administrator' : 'supervisor';
    }
}
