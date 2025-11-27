<?php

namespace App\Filament\Exports;

use App\Models\Deadline;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class DeadlineExporter extends Exporter
{
    protected static ?string $model = Deadline::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('#')
                ->enabledByDefault(false),
            ExportColumn::make('scope_type_id')
                ->label('Ambito')
                ->formatStateUsing(fn ($record) => $record->scopeType->name ?? 'N/D'),
            ExportColumn::make('deadline_date')
                ->label('Scadenza')
                ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : 'N/D'),
            ExportColumn::make('recurrent')
                ->label('Ricorrente')
                ->formatStateUsing(fn ($state) => $state ? 'Sì' : 'No'),
            ExportColumn::make('timespan')
                ->label('Periodicità')
                ->formatStateUsing(function ($record) {
                    if (!$record->recurrent) {
                        return 'Non periodica';
                    }
                    if ($record->timespan && $record->quantity) {
                        return $record->quantity . ' ' . $record->timespan->getLabel();
                    }
                    return 'N/D';
                }),
            ExportColumn::make('description')
                ->label('Descrizione')
                ->formatStateUsing(fn ($state) => $state ?? 'N/D'),
            ExportColumn::make('met')
                ->label('Rispettata')
                ->formatStateUsing(function ($record, $state) {
                    $deadline = \Carbon\Carbon::parse($record->deadline_date);
                    if ($deadline->isFuture() || $deadline->isToday()) {
                        return '';
                    }
                    return $state ? 'Sì' : 'No';
                }),
            ExportColumn::make('met_date')
                ->enabledByDefault(false),
            ExportColumn::make('met_user_id')
                ->enabledByDefault(false),
            ExportColumn::make('note')
                ->enabledByDefault(false),
            ExportColumn::make('insert_user_id')
                ->enabledByDefault(false),
            ExportColumn::make('modify_user_id')
                ->enabledByDefault(false),
            ExportColumn::make('renew')
                ->enabledByDefault(false),
            ExportColumn::make('created_at')
                ->enabledByDefault(false),
            ExportColumn::make('updated_at')
                ->enabledByDefault(false),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your deadline export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
