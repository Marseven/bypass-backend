<?php

namespace App\Enums;

enum DureeType: string
{
    case CourtTerme = 'court_terme';
    case LongTerme = 'long_terme';

    public function label(): string
    {
        return match ($this) {
            self::CourtTerme => 'Court terme (< 48h)',
            self::LongTerme => 'Long terme (>= 48h)',
        };
    }

    public static function fromDurationHours(int $hours): self
    {
        return $hours < 48 ? self::CourtTerme : self::LongTerme;
    }
}
