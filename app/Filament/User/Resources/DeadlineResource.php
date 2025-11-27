<?php

namespace App\Filament\User\Resources;

use App\Enums\Permission;
use App\Enums\Timespan;
use App\Filament\User\Resources\DeadlineResource\Pages;
use App\Filament\User\Resources\DeadlineResource\RelationManagers;
use App\Models\Deadline;
use App\Models\ScopeType;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
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
            // ->disabled(function ($record) use($form) {
            //     // if ($form->getOperation() === 'edit' && !Auth::user()->is_admin) {
            //     if ($form->getOperation() === 'edit' && !Auth::user()->hasRole('super_admin')) {
            //         return optional(Auth::user()->scopeTypes                                    // se l'utente non è admin deve avere permessi non di lettura
            //                 ->where('id', optional($record)->scope_type_id)
            //                 ->first())->pivot->permission === Permission::READ->value;
            //     }
            //     return false;                                                                   // se è admin o siamo in create il form è abilitato
            // })
            ->schema([
                Select::make('scope_type_id')->label('Ambito')
                    ->relationship(
                        name: 'scopeType',
                        titleAttribute: 'name',
                        modifyQueryUsing: function ($query) {                                   // filtro opzioni ambiti con permesso di scrittura
                            // if ((bool) Auth::user()->is_admin) {
                            if ((bool) Auth::user()->hasRole('super_admin')) {
                                return $query->orderBy('position');                             // se l'utente è admin, mostra tutti gli scope types
                            }
                            return $query->whereIn('scope_types.id', function ($subQuery) {     // altrimenti filtra gli scope types dell'utente con permesso diverso da READ
                                $subQuery->select('scope_type_id')
                                    ->from('user_scope_type')
                                    ->where('user_id', Auth::user()->id)
                                    ->where('permission', '!=', Permission::READ->value);
                            })->orderBy('position');
                        }
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->columnSpan(['sm' => 'full', 'md' => 3]),
                DatePicker::make('deadline_date')->label('Scadenza')
                    ->required()
                    ->extraInputAttributes(['class' => 'text-center'])
                    ->columnSpan(['sm' => 'full', 'md' => 2]),
                Toggle::make('recurrent')->label('Scadenza periodica')
                    ->live()
                    ->columnSpan(['sm' => 'full', 'md' => 3]),
                TextInput::make('quantity')->label('Quantità')
                    ->required()
                    ->extraInputAttributes(['class' => 'text-right'])
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
                    ->extraInputAttributes(['class' => 'text-center'])
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
                    ->extraInputAttributes(['class' => 'text-center'])
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
                    ->extraInputAttributes(['class' => 'text-center'])
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
            ->query(Deadline::userTypes())
            ->defaultSort('deadline_date', 'asc')
            ->columns([
                TextColumn::make('scopeType.name')->label('Ambito'),
                TextColumn::make('description')
                    ->label('Descrizione')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->description),
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
                    ->formatStateUsing(function ($record, $state) {
                        $deadline = \Carbon\Carbon::parse($record->deadline_date);
                        if (!$state && ($deadline->isFuture() || $deadline->isToday())) {
                            return '';
                        }
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
                SelectFilter::make('scope_type_id')->label('Ambito')
                    ->relationship(
                        name: 'scopeType',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->orderBy('position')
                    )
                    ->searchable()
                    ->multiple()->preload(),
                SelectFilter::make('timespan')
                    ->label('Periodicità')
                    ->options(function () {
                        $options = ['null' => 'Non periodica'];                                             // creo un array con l'opzione per "Non periodica" (timespan null)

                        foreach (Timespan::cases() as $case) {
                            $options[$case->value] = $case->getLabel();                                     // aggiungo le opzioni dell'enum Timespan
                        }

                        return $options;
                    })
                    ->modifyQueryUsing(function (Builder $query, $state) {
                        if (empty($state['values'])) {
                            return $query;                                                                  // se il filtro non è stato usato (nessuna selezione), non modifico la query
                        }

                        if (in_array('null', $state['values'])) {                                           // "Non periodica" è selezionata
                            $otherValues = array_diff($state['values'], ['null']);                          // rimuovo 'null' dall'array per ottenere le altre opzioni selezionate

                            if (empty($otherValues)) {                                                      // solo "Non periodica" è selezionata
                                $query->whereNull('timespan');
                            } else {                                                                        // altre opzioni sono selezionate insieme a "Non periodica"
                                $query->where(function ($q) use ($otherValues) {
                                    $q->whereNull('timespan')
                                    ->orWhereIn('timespan', $otherValues);
                                });
                            }
                        } else {                                                                            // "Non periodica" non è tra le opzioni selezionate
                            $query->whereIn('timespan', $state['values']);
                        }

                        return $query;
                    })
                    ->multiple()
                    ->preload(),
                // SelectFilter::make('show_met')
                //     ->label('Rispettate')
                //     ->options([
                //         '0' => 'Non rispettate',
                //         '1' => 'Rispettate'
                //     ])
                //     ->modifyQueryUsing(function (Builder $query, $state) {
                //         if ($state['value'] !== null && $state['value'] !== '') {
                //             $query->where('met', (bool) $state['value']);
                //         }

                //         return $query;
                //     }),
                SelectFilter::make('stato_scadenza')
                    ->label('Stato Scadenza')
                    ->options([
                        'respected'    => 'Rispettate (Sì)',
                        'not_met_late' => 'Non Rispettate (No)',
                        'in_progress'  => 'In Corso (Non Scadute)', // Nuova opzione
                    ])
                    ->modifyQueryUsing(function (Builder $query, array $state): Builder {
                        if (!isset($state['value']) || $state['value'] === null || $state['value'] === '') {
                            return $query;
                        }
                        switch ($state['value']) {
                            case 'respected':
                                // Mostra solo i record dove met è TRUE.
                                return $query->where('met', true);
                            case 'not_met_late':
                                // Mostra i record dove met è FALSE E la data è scaduta.
                                return $query
                                    ->where('met', false)
                                    ->whereDate('deadline_date', '<', now());
                            case 'in_progress':
                                // Mostra i record dove la data NON è scaduta.
                                return $query->whereDate('deadline_date', '>=', now());
                        }

                        return $query;
                    }),
                Filter::make('deadline_period')
                    ->form([
                        DatePicker::make('deadline_from')
                            ->label('Data scadenza da')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->placeholder('Seleziona data inizio'),

                        Forms\Components\DatePicker::make('deadline_to')
                            ->label('Data scadenza a')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->placeholder('Seleziona data fine'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['deadline_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('deadline_date', '>=', $date),
                            )
                            ->when(
                                $data['deadline_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('deadline_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        $from = $data['deadline_from'] ?? null;
                        $to = $data['deadline_to'] ?? null;

                        if ($from && $to) {                                                                                         // entrambe le date sono selezionate
                            $indicators[] = Indicator::make('Scadenze dal ' . \Carbon\Carbon::parse($from)->format('d/m/Y') . '
                                                al ' . \Carbon\Carbon::parse($to)->format('d/m/Y'))
                                ->removeField('deadline_from')
                                ->removeField('deadline_to');
                        } else {
                            if ($from) {                                                                                            // è selezionata solo la data di inizio
                                $indicators[] = Indicator::make('Scadenza da: ' . \Carbon\Carbon::parse($from)->format('d/m/Y'))
                                    ->removeField('deadline_from');
                            }
                            if ($to) {                                                                                              // è selezionata solo la data di fine
                                $indicators[] = Indicator::make('Scadenza a: ' . \Carbon\Carbon::parse($to)->format('d/m/Y'))
                                    ->removeField('deadline_to');
                            }
                        }

                        return $indicators;
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'view' => Pages\ViewDeadline::route('/{record}'),
        ];
    }
}
