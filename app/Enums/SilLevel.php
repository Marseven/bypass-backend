<?php

namespace App\Enums;

enum SilLevel: string
{
    case NA = 'na';
    case SIL1 = 'sil1';
    case SIL2 = 'sil2';
    case SIL3 = 'sil3';

    public function label(): string
    {
        return match ($this) {
            self::NA => 'N/A',
            self::SIL1 => 'SIL 1',
            self::SIL2 => 'SIL 2',
            self::SIL3 => 'SIL 3',
        };
    }

    public function isSecurityRelevant(): bool
    {
        return in_array($this, [self::SIL1, self::SIL2, self::SIL3]);
    }
}
