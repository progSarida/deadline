<?php

namespace App\Filament\Resources;

use App\Enums\Permission;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\ScopeType;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    public static ?string $pluralModelLabel = 'Utenti';
    public static ?string $modelLabel = 'Utente';
    protected static ?string $navigationIcon = 'heroicon-s-users';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(6)
            ->schema([
                TextInput::make('name')->label('Nome')
                    ->required()
                    ->columnSpan(2)
                    ->maxLength(255),
                TextInput::make('email')->label('Email')
                    ->required()
                    ->columnSpan(2)
                    ->maxLength(255),
                TextInput::make('password')->label('Password')
                    ->columnSpan(2)
                    ->maxLength(255)
                    ->password()
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn($livewire) => $livewire instanceof \App\Filament\Resources\UserResource\Pages\CreateUser),
                Toggle::make('is_admin')->label('Amministratore')
                    ->columnSpan(2)
                    ->onColor('success')
                    ->offColor('danger'),
                Section::make('Ambiti e Permessi')
                    ->collapsed()
                    ->schema([
                        Repeater::make('scopeTypes')
                            ->label('')
                            ->schema([
                                Select::make('scope_type_id')
                                    ->label('Ambito')
                                    ->options(ScopeType::pluck('name', 'id')->toArray())
                                    ->required()
                                    ->distinct()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(2),
                                Select::make('permission')
                                    ->label('Permesso')
                                    ->options(Permission::class)
                                    ->required()
                                    ->columnSpan(2),
                            ])
                            ->columns(4)
                            ->itemLabel(fn (array $state): ?string =>
                                $state['scope_type_id'] && isset($state['permission'])
                                    ? (ScopeType::find($state['scope_type_id'])?->name . ' - ' . Permission::from($state['permission'])->getLabel())
                                    : 'Nuovo Ambito'
                            )
                            ->addActionLabel('Aggiungi Ambito')
                            ->deleteAction(
                                fn($action) => $action->requiresConfirmation()
                            )
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                return $data;
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                return $data;
                            })
                            ->saveRelationshipsUsing(function ($component, $state, $record) {
                                $record->scopeTypes()->detach();

                                foreach ($state as $assignment) {
                                    if (isset($assignment['scope_type_id'])) {
                                        $record->scopeTypes()->attach($assignment['scope_type_id'], [
                                            'permission' => $assignment['permission'],
                                        ]);
                                    }
                                }
                            })
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if (!$record || !$record->exists) {
                                    return;
                                }

                                $scopeTypes = $record->scopeTypes()->get();
                                $assignments = [];

                                foreach ($scopeTypes as $scopeType) {
                                    $assignments[] = [
                                        'scope_type_id' => $scopeType->id,
                                        'permission' => $scopeType->pivot->permission,
                                    ];
                                }

                                $component->state($assignments);
                            }),
                    ])
                    ->columnSpan(['sm' => 'full', 'md' => 12]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nome'),
                TextColumn::make('email')->label('Email'),
                ToggleColumn::make('is_admin')
                    ->label('Amministratore')
                    ->onIcon('heroicon-s-check-circle')
                    ->offIcon('heroicon-s-x-circle')
                    ->onColor('success')
                    ->offColor('danger'),
            ])
            ->filters([
                //
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Gestione';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }
}
