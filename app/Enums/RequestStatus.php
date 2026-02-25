<?php

namespace App\Enums;

enum RequestStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Approved = 'approved';
    case Active = 'active';
    case Closed = 'closed';
    case Expired = 'expired';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Pending => 'En attente',
            self::Approved => 'Approuvé',
            self::Active => 'Actif',
            self::Closed => 'Clôturé',
            self::Expired => 'Expiré',
            self::Rejected => 'Rejeté',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Closed, self::Expired, self::Rejected]);
    }
}
