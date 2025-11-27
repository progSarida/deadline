<?php

namespace App\Filament\User\Resources\DeadlineResource\Pages;

use App\Enums\Permission;
use App\Filament\Exports\DeadlineExporter;
use App\Filament\User\Resources\DeadlineResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Actions\ExportAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;

class ListDeadlines extends ListRecords
{
    protected static string $resource = DeadlineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(function () {
                    // if(Auth::user()->is_admin)
                    if(Auth::user()->hasRole('super_admin'))
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
            Actions\Action::make('print')
                    ->icon('heroicon-o-printer')
                    ->label('Stampa')
                    ->tooltip('Stampa elenco scadenze')
                    ->color(Color::rgb('rgb(255, 0, 0)'))
                    ->action(function ($livewire) {
                        $records = $livewire->getFilteredTableQuery()->get();
                        $filters = $livewire->tableFilters ?? [];
                        $search = $livewire->tableSearch ?? null;

                        if(count($records) === 0){
                            Notification::make()
                                ->title('Nessun elemento da stampare')
                                ->warning()
                                ->send();
                            return false;
                        }

                        return response()
                            ->streamDownload(function () use ($records, $search, $filters) {
                                echo Pdf::loadHTML(
                                    Blade::render('print.deadlines', [
                                        'deadlines' => $records,
                                        'search' => $search,
                                        'filters' => $filters,
                                    ])
                                )
                                    ->setPaper('A4', 'landscape')
                                    ->stream();
                            }, 'Scadenze.pdf');

                        Notification::make()
                            ->title('Stampa avviata')
                            ->success()
                            ->send();
                    }),
            ExportAction::make('esporta')
                ->icon('heroicon-s-table-cells')
                ->label('Esporta')
                ->tooltip('Esporta elenco scadenze')
                ->color(Color::rgb('rgb(0,153,0)'))
                ->exporter(DeadlineExporter::class),
        ];
    }

    public function getMaxContentWidth(): MaxWidth|string|null                                  // allarga la tabella a tutta pagina
    {
        return MaxWidth::Full;
    }
}
