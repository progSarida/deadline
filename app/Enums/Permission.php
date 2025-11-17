<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Permission: string implements HasLabel
{
    case READ = "read";
    case WRITE = "write";
    case DELETE = "delete";

    public function getLabel(): string
    {
        return match($this) {
            self::READ => 'Lettura',
            self::WRITE => 'Lettura/Scrittura',
            self::DELETE => 'Lettura/Scrittura/Cancellazione',
        };
    }
}
