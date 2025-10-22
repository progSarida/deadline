<?php

namespace App\Filament\User\Resources;

use App\Enums\Timespan;
use App\Filament\User\Resources\DeadlineResource\Pages;
use App\Filament\User\Resources\DeadlineResource\RelationManagers;
use App\Models\Deadline;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DeadlineResource extends Resource
{
    protected static ?string $model = Deadline::class;
    public static ?string $pluralModelLabel = 'Scadenze';
    protected static ?string $navigationLabel = 'Elenco scadenze';
    public static ?string $modelLabel = 'Scadenza';
    protected static ?string $navigationIcon = 'fas-calendar-alt';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('scope_type_id')->label('Ambito'),
                TextColumn::make('description')->label('Descrizione')
                    ->searchable(),
                TextColumn::make('timespanQuantity')->label('Periodicità')
                    ->formatStateUsing(function ($record) {
                        return $record->recurrent ? $record->quantity . ' ' . $record->timespan->getLabel() : '';
                    }),
                TextColumn::make('deadline_date')
                    ->label('Gara')
                    ->date('d/m/Y'),
                ToggleColumn::make('met')
                    ->label('Rispettata')
                    ->onIcon('heroicon-s-check-circle')
                    ->offIcon('heroicon-s-x-circle')
                    ->onColor('success')
                    ->offColor('danger'),
                TextColumn::make('registration_date')
                    ->label('Registrato il')
                    ->date('d/m/Y'),
                TextColumn::make('registrationUser.name')
                    ->searchable()
                    ->label('Registrato da'),
            ])
            ->filters([
                SelectFilter::make('scope_tyoe_id')->label('Ambito')
                    ->relationship(
                        name: 'scope',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->orderBy('position')
                    )
                    ->searchable()
                    ->multiple()->preload(),
                SelectFilter::make('timespan')->label('Periodicità')
                    ->options(Timespan::class)
                    ->multiple()->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeadlines::route('/'),
            'create' => Pages\CreateDeadline::route('/create'),
            'edit' => Pages\EditDeadline::route('/{record}/edit'),
        ];
    }
}
