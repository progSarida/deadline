<?php

namespace App\Filament\User\Resources\DeadlineResource\RelationManagers;

use App\Enums\Permission;
use App\Enums\Timespan;
use App\Filament\User\Resources\DeadlineResource;
use App\Models\Deadline;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LinkedDeadlinesRelationManager extends RelationManager
{
    // relazione "reale" (rinnovi diretti): la tabella mostra però l'intera serie, vedi getTableQuery()
    protected static string $relationship = 'nextDeadlines';

    protected static ?string $title = 'Scadenze collegate';

    protected static ?string $icon = 'fas-calendar-alt';

    /**
     * Sempre false: il componente non deve essere renderizzato in automatico in fondo alle pagine,
     * perché è già incluso nel form dentro la Section "Scadenze collegate" (vedi DeadlineResource::form()).
     * Resta comunque elencato in DeadlineResource::getRelations(), che è ciò che lo registra come componente Livewire.
     * La visibilità sulle sole scadenze periodiche è gestita dalla Section.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return false;
    }

    // il titolo lo mostra la Section che lo contiene: stringa vuota (non null, altrimenti Filament ripiega su $title)
    protected function getTableHeading(): string
    {
        return '';
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    // sostituisco la query della relazione con quella dell'intera catena di rinnovi
    protected function getTableQuery(): Builder
    {
        $owner = $this->getOwnerRecord();

        return Deadline::query()
            ->userTypes()                                                               // stesso filtro ambiti del resource
            ->whereIn('id', $owner->seriesIds())
            ->whereKeyNot($owner->getKey());                                            // escludo la scadenza corrente
    }

    // stesso form di DeadlineResource: usato dalla modale della ViewAction
    public function form(Form $form): Form
    {
        return $form
            ->columns(48)
            ->schema([
                Select::make('scope_type_id')->label('Ambito')
                    ->relationship(
                        name: 'scopeType',
                        titleAttribute: 'name',
                        modifyQueryUsing: function ($query) {                                   // filtro opzioni ambiti con permesso di scrittura
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
                    ->columnSpan(['sm' => 'full', 'md' => 11]),
                DatePicker::make('deadline_date')->label('Data scadenza')
                    ->required()
                    ->extraInputAttributes(['class' => 'text-center'])
                    ->columnSpan(['sm' => 'full', 'md' => 7]),
                TimePicker::make('deadline_time')->label('Orario scadenza')
                    ->format('H:i')
                    ->seconds(false)
                    ->extraInputAttributes(['class' => 'text-center'])
                    ->columnSpan(['sm' => 'full', 'md' => 7]),
                Toggle::make('recurrent')->label('Scadenza periodica')
                    ->live()
                    ->columnSpan(['sm' => 'full', 'md' => 9]),
                TextInput::make('quantity')->label('Frequenza:')
                    ->required()
                    ->numeric()
                    ->extraInputAttributes(['class' => 'text-right'])
                    ->columnSpan(['sm' => 'full', 'md' => 5])
                    ->visible(fn (callable $get) => $get('recurrent'))
                    ->default(1),
                Select::make('timespan')->label('Periodicità')
                    ->required()
                    ->live()
                    ->options(Timespan::class)
                    ->columnSpan(['sm' => 'full', 'md' => 9])
                    ->visible(fn (callable $get) => $get('recurrent')),
                TextInput::make('description')->label('Descrizione')
                    ->live()
                    ->columnSpan(['sm' => 'full', 'md' => 'full']),
                Placeholder::make('')->visible(fn ($record) => !is_null($record))
                    ->columnSpan(['sm' => 0, 'md' => 24]),
                Toggle::make('met')->label('Rispettata')
                    ->live()
                    ->visible(fn ($record) => !is_null($record))
                    ->afterStateUpdated(function ($set, $state) {
                        $set('met_date', now()->toDateString());
                        $set('met_user_id', Auth::user()->id);
                    })
                    ->columnSpan(['sm' => 'full', 'md' => 8]),
                DatePicker::make('met_date')->label('Rispettata in data')
                    ->required()
                    ->extraInputAttributes(['class' => 'text-center'])
                    ->visible(fn (callable $get) => $get('met'))
                    ->columnSpan(['sm' => 'full', 'md' => 8]),
                Select::make('met_user_id')->label('Rispettata da')
                    ->required()
                    ->relationship(name: 'metUser', titleAttribute: 'name')
                    ->visible(fn (callable $get) => $get('met'))
                    ->columnSpan(['sm' => 'full', 'md' => 8]),
                Textarea::make('note')->label('Note')
                    ->rows(4)
                    ->columnSpan(['sm' => 'full', 'md' => 'full']),
                DatePicker::make('created_at')->label('Data inserimento')
                    ->disabled()
                    ->extraInputAttributes(['class' => 'text-center'])
                    ->visible(fn (callable $get) => $get('insert_user_id'))
                    ->columnSpan(['sm' => 'full', 'md' => 8]),
                Select::make('insert_user_id')->label('Inserito da')
                    ->required()
                    ->relationship(name: 'insertUser', titleAttribute: 'name')
                    ->disabled()
                    ->visible(fn ($state) => $state !== null)
                    ->columnSpan(['sm' => 'full', 'md' => 8]),
                DatePicker::make('updated_at')->label('Data modifica')
                    ->disabled()
                    ->extraInputAttributes(['class' => 'text-center'])
                    ->visible(fn (callable $get) => $get('modify_user_id'))
                    ->columnSpan(['sm' => 'full', 'md' => 8]),
                Select::make('modify_user_id')->label('Modificato da')
                    ->relationship(name: 'modifyUser', titleAttribute: 'name')
                    ->disabled()
                    ->visible(fn ($state) => $state !== null)
                    ->columnSpan(['sm' => 'full', 'md' => 8]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('deadline_date', 'asc')
            ->defaultPaginationPageOption(5)                                             // righe mostrate all'apertura
            ->paginationPageOptions([5, 10, 25])
            ->emptyStateHeading('Nessuna scadenza collegata')
            ->emptyStateDescription('Questa scadenza periodica non è ancora stata rinnovata.')
            ->columns([
                TextColumn::make('posizione')
                    ->label('Posizione')
                    ->badge()
                    ->getStateUsing(fn ($record) => $this->isBeforeOwnerRecord($record) ? 'Precedente' : 'Successiva')
                    ->color(fn ($state) => $state === 'Precedente' ? 'warning' : 'info'),
                TextColumn::make('deadline_date')
                    ->label('Scadenza')
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        $date = \Carbon\Carbon::parse($record->deadline_date);
                        if ($record->deadline_time) {
                            $time = \Carbon\Carbon::parse($record->deadline_time)->format('H:i');
                            return "{$date->format('d/m/Y')} ({$time})";
                        }
                        return $date->format('d/m/Y');
                    }),
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
                    ->label('Rispettata il')
                    ->date('d/m/Y'),
                TextColumn::make('metUser.name')
                    ->label('Rispettata da'),
                TextColumn::make('description')
                    ->label('Descrizione')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->description),
                TextColumn::make('created_at')
                    ->label('Inserita il')
                    ->date('d/m/Y'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalWidth(MaxWidth::SevenExtraLarge)
                    // ->url(fn ($record) => DeadlineResource::getUrl('view', ['record' => $record])),
                // Tables\Actions\Action::make('edit')
                //     ->label('Modifica')
                //     ->icon('heroicon-m-pencil-square')
                //     ->url(fn ($record) => DeadlineResource::getUrl('edit', ['record' => $record])),
            ]);
    }

    // true se la scadenza precede quella aperta nella pagina
    protected function isBeforeOwnerRecord(Model $record): bool
    {
        $owner = $this->getOwnerRecord();

        $recordDate = \Carbon\Carbon::parse($record->deadline_date);
        $ownerDate = \Carbon\Carbon::parse($owner->deadline_date);

        if ($recordDate->equalTo($ownerDate)) {
            return $record->getKey() < $owner->getKey();
        }

        return $recordDate->lessThan($ownerDate);
    }
}
