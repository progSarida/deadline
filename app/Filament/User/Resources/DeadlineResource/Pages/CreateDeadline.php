<?php

namespace App\Filament\User\Resources\DeadlineResource\Pages;

use App\Filament\User\Resources\DeadlineResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDeadline extends CreateRecord
{
    protected static string $resource = DeadlineResource::class;
}
