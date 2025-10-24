<?php

namespace App\Filament\User\Resources\DeadlineResource\Pages;

use App\Enums\Permission;
use App\Filament\User\Resources\DeadlineResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;

class ListDeadlines extends ListRecords
{
    protected static string $resource = DeadlineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(function () {
                    if(Auth::user()->is_admin)
                        return true;
                    else {
                        $scopes = Auth::user()->scopeTypes;
                        $noRead = false;
                        foreach($scopes as $scope) {
                            $noRead = $scope->pivot->permission !== Permission::READ->value;
                        }
                        return $noRead;
                    }
                }),
        ];
    }

    public function getMaxContentWidth(): MaxWidth|string|null                                  // allarga la tabella a tutta pagina
    {
        return MaxWidth::Full;
    }
}
