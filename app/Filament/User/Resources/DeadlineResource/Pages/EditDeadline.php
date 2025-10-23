<?php

namespace App\Filament\User\Resources\DeadlineResource\Pages;

use App\Filament\User\Resources\DeadlineResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeadline extends EditRecord
{
    protected static string $resource = DeadlineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->refreshFormData(['updated_at', 'modify_user_id']);                   // ricarico i campi specificati
    }
}
