<?php

namespace App\Filament\User\Resources;

use App\Enums\Timespan;
use App\Filament\User\Resources\DeadlineResource\Pages;
use App\Filament\User\Resources\DeadlineResource\RelationManagers;
use App\Models\Deadline;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

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
            ->columns(12)
            ->schema([
                Select::make('scope_type_id')->label('Ambito')
                    ->relationship(name: 'scopeType', titleAttribute: 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->columnSpan(['sm' => 'full', 'md' => 3]),
                DatePicker::make('deadline_date')->label('Scadenza')
                    ->required()
                    ->columnSpan(['sm' => 'full', 'md' => 2]),
                Toggle::make('recurrent')->label('Scadenza periodica')
                    ->live()
                    ->columnSpan(['sm' => 'full', 'md' => 3]),
                TextInput::make('quantity')->label('Quantità')
                    ->required()
                    ->columnSpan(['sm' => 'full', 'md' => 1])
                    ->visible(fn (callable $get) => $get('recurrent')),
                Select::make('timespan')->label('Periodicità')
                    ->required()
                    ->live()
                    ->options(Timespan::class)
                    ->columnSpan(['sm' => 'full', 'md' => 3])
                    ->visible(fn (callable $get) => $get('recurrent')),
                TextInput::make('description')->label('Descrizione')
                    ->live()
                    ->columnSpan(['sm' => 'full', 'md' => 12]),
                Placeholder::make('')->visible(fn ($record) => !is_null($record))
                    ->columnSpan(['sm' => 0, 'md' => 6]),
                Toggle::make('met')->label('Rispettata')
                    ->live()
                    ->visible(fn ($record) => !is_null($record))
                    ->afterStateUpdated(function ($set, $state) {
                        $set('met_date', now()->toDateString());
                        $set('met_user_id', Auth::user()->id);
                    })
                    ->columnSpan(['sm' => 'full', 'md' => 2]),
                DatePicker::make('met_date')->label('Rispetatta il')
                    ->required()
                    ->visible(fn (callable $get) => $get('met'))
                    ->columnSpan(['sm' => 'full', 'md' => 2]),
                Select::make('met_user_id')->label('Rispettata da')
                    ->required()
                    ->relationship(name: 'metUser', titleAttribute: 'name')
                    ->visible(fn (callable $get) => $get('met'))
                    ->columnSpan(['sm' => 'full', 'md' => 2]),
                Textarea::make('note')->label('Note')
                    ->rows(4)
                    ->columnSpan(['sm' => 'full', 'md' => 12]),
                DatePicker::make('created_at')->label('Data inserimento')
                    ->disabled()
                    ->visible(fn (callable $get) => $get('insert_user_id'))
                    ->columnSpan(['sm' => 'full', 'md' => 2]),
                Select::make('insert_user_id')->label('Inserito da')
                    ->required()
                    ->relationship(name: 'insertUser', titleAttribute: 'name')
                    ->disabled()
                    ->visible(fn ($state) => $state !== null)
                    ->columnSpan(['sm' => 'full', 'md' => 2]),
                DatePicker::make('updated_at')->label('Data modifica')
                    ->disabled()
                    ->visible(fn (callable $get) => $get('modify_user_id'))
                    ->columnSpan(['sm' => 'full', 'md' => 2]),
                Select::make('modify_user_id')->label('Modificato da')
                    ->relationship(name: 'modifyUser', titleAttribute: 'name')
                    ->disabled()
                    ->visible(fn ($state) => $state !== null)
                    ->columnSpan(['sm' => 'full', 'md' => 2]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('deadline_date', 'asc')
            ->columns([
                TextColumn::make('scopeType.name')->label('Ambito'),
                TextColumn::make('description')->label('Descrizione')
                    ->searchable(),
                TextColumn::make('recurrent')
                    ->label('Periodica')
                    ->formatStateUsing(function ($state) {
                        return $state ? 'Sì' : 'No';
                    })
                ->sortable(),
                TextColumn::make('timespan')->label('Periodicità')
                    ->formatStateUsing(function ($record) {
                        if (!$record->recurrent) {
                            return 'Non periodica';
                        }
                        if ($record->timespan && $record->quantity) {
                            return $record->quantity . ' ' . $record->timespan->getLabel();
                        }
                        return 'N/D';
                    }),
                TextColumn::make('deadline_date')
                    ->label('Scadenza')
                    ->date('d/m/Y'),
                TextColumn::make('created_at')
                    ->label('Inserita il')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('insertUser.name')
                    ->searchable()
                    ->label('Inserita da')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Modificata il')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('modifyUser.name')
                    ->searchable()
                    ->label('Modificata da')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('met')
                    ->label('Rispettata')
                    ->formatStateUsing(function ($state) {
                        return $state ? 'Sì' : 'No';
                    }),
                TextColumn::make('met_date')
                    ->label('Rispettato il')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('metUser.name')
                    ->searchable()
                    ->label('Modificato da')
                    ->toggleable(isToggledHiddenByDefault: true),
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
