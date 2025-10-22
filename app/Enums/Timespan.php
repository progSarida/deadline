<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Timespan: string implements HasLabel
{
    case HOUR = 'hour';
    case DAY = "day";
    case WEEK = "week";
    case MONTH = "month";
    case YEAR = "year";

    public function getLabel(): string
    {
        return match($this) {
            self::HOUR => 'Ora/e',
            self::DAY => 'Giorno/i',
            self::WEEK => 'Settimana/e',
            self::MONTH => 'Mese/i',
            self::YEAR => 'anno/i',
        };
    }
}
