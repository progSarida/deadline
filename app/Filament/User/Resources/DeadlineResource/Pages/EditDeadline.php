<?php

namespace App\Filament\User\Resources\DeadlineResource\Pages;

use App\Enums\Permission;
use App\Filament\User\Resources\DeadlineResource;
use App\Models\Deadline;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class EditDeadline extends EditRecord
{
    protected static string $resource = DeadlineResource::class;

    public function getTitle(): string | Htmlable
    {
        return $this->record->description;
    }

    public bool $shouldShowRenewModal = false;

    protected function getHeaderActions(): array
    {
        $current = $this->record;

        // Applica lo scope di filtro (Deadline::userTypes())
        $query = \App\Models\Deadline::userTypes();
        // Precedente per deadline_date: data precedente O stessa data con ID minore
        $previous = (clone $query)
            ->where(function ($q) use ($current) {
                $q->where('deadline_date', '<', $current->deadline_date) // Data precedente
                  ->orWhere(function ($q2) use ($current) {
                      $q2->where('deadline_date', $current->deadline_date) // Stessa data
                         ->where('id', '<', $current->id);              // ID precedente
                  });
            })
            ->orderBy('deadline_date', 'desc')->orderBy('id', 'desc')->first();
        // Successivo per deadline_date: data successiva O stessa data con ID maggiore
        $next = (clone $query)
            ->where(function ($q) use ($current) {
                $q->where('deadline_date', '>', $current->deadline_date) // Data successiva
                  ->orWhere(function ($q2) use ($current) {
                      $q2->where('deadline_date', $current->deadline_date) // Stessa data
                         ->where('id', '>', $current->id);              // ID successivo
                  });
            })
            ->orderBy('deadline_date', 'asc')->orderBy('id', 'asc')->first();

        return [
            // Scorrimento
            Actions\Action::make('previous_doc')
                ->label('Precedente')
                ->color('info')
                ->icon('heroicon-o-arrow-left-circle')
                ->visible(function () use ($previous) { return $previous;})
                ->action(function () use ($previous) {
                    $this->redirect(DeadlineResource::getUrl('edit', ['record' => $previous->id]));
                }),
            Actions\Action::make('next_doc')
                ->label('Successiva')
                ->color('info')
                ->icon('heroicon-o-arrow-right-circle')
                ->visible(function () use ($next) { return $next;})
                ->action(function () use ($next) {
                    $this->redirect(DeadlineResource::getUrl('edit', ['record' => $next->id]));
                }),
        ];
    }

    // Sovrascrivo il metodo di salvataggio
    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // Salvo i dati del record
        $record->update($data);

        // Controllo se la scadenza è stata appena marcata come rispettata
        // e se è ricorrente e non è già stata rinnovata
        if ($data['met'] && $record->recurrent && !$record->renew) {
            // Imposto un flag per far partire l'action dopo il salvataggio
            $this->shouldShowRenewModal = true;
        }

        return $record;
    }

    protected function afterSave(): void
    {
        $this->refreshFormData(['updated_at', 'modify_user_id']);

        // Se il flag è impostato, mostro la modale di rinnovo
        if (property_exists($this, 'shouldShowRenewModal') && $this->shouldShowRenewModal) {
            $this->shouldShowRenewModal = false;

            // Trigger dell'action di rinnovo
            $this->mountAction('renewDeadlineAfterSave');
        }
    }

    // Action per il rinnovo dopo il salvataggio
    public function renewDeadlineAfterSaveAction(): Action
    {
        $currentDeadline = $this->record;
        $defaultDate = $this->calculateDefaultDate($currentDeadline);

        return Action::make('renewDeadlineAfterSave')
            ->requiresConfirmation()
            ->modalHeading('Rinnovo Scadenza')
            ->modalDescription('La scadenza è stata rispettata. Vuoi creare la nuova scadenza periodica?')
            ->modalWidth('md')
            ->modalSubmitActionLabel('Crea nuova scadenza')
            ->modalCancelActionLabel('Annulla')
            ->form([
                DatePicker::make('new_deadline_date')
                    ->label('Nuova scadenza proposta: ' . Carbon::parse($defaultDate)->format('d/m/Y'))
                    ->extraInputAttributes(['class' => 'text-center'])
                    ->required(),
            ])
            ->action(function (array $data) use ($currentDeadline, $defaultDate) {
                if ($data['new_deadline_date'] !== $defaultDate) {
                    // Se la data è diversa, invia una notifica di avviso
                    $newDateFormatted = Carbon::parse($data['new_deadline_date'])->format('d/m/Y');
                    $defaultDateFormatted = Carbon::parse($defaultDate)->format('d/m/Y');

                    Notification::make('warning')
                        ->title('Data Modificata Manualmente')
                        ->body("Inserita la data {$newDateFormatted} invece<br> di quella proposta: {$defaultDateFormatted}")
                        ->warning()
                        ->persistent()
                        ->send();
                }
                try {
                    $new = Deadline::create([
                        'prev_deadline_id' => $currentDeadline->id,
                        'scope_type_id' => $currentDeadline->scope_type_id,
                        'deadline_date' => $data['new_deadline_date'],
                        'recurrent' => $currentDeadline->recurrent,
                        'quantity' => $currentDeadline->quantity,
                        'timespan' => $currentDeadline->timespan,
                        'description' => $currentDeadline->description,
                        'met' => false,
                        'met_date' => null,
                        'met_user_id' => null,
                        'note' => $currentDeadline->note,
                        'insert_user_id' => Auth::user()->id,
                        'modify_user_id' => Auth::user()->id,
                        'renew' => false,
                    ]);

                    $currentDeadline->update([
                        'renew' => true
                    ]);

                    Notification::make('success')
                        ->title('Scadenza rinnovata con successo!')
                        ->success()
                        ->send();

                    // Opzionale: redirect alla lista
                    $this->redirect(DeadlineResource::getUrl('view', ['record' => $new->id]));
                } catch (\Exception $e) {
                    Notification::make('error')
                        ->title('Errore durante il rinnovo della scadenza: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    // Calcolo la data della nuova scadenza periodica
    protected function calculateDefaultDate(Deadline $deadline): string
    {
        $date = Carbon::parse($deadline->deadline_date);

        switch ($deadline->timespan) {
            case \App\Enums\Timespan::HOUR:
                $date->addHours($deadline->quantity);
                break;
            case \App\Enums\Timespan::DAY:
                $date->addDays($deadline->quantity);
                break;
            case \App\Enums\Timespan::WEEK:
                $date->addWeeks($deadline->quantity);
                break;
            case \App\Enums\Timespan::MONTH:
                $date->addMonths($deadline->quantity);
                break;
            case \App\Enums\Timespan::YEAR:
                $date->addYears($deadline->quantity);
                break;
            default:
                return now()->toDateString();
        }

        return $date->toDateString();
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()->color('success'),
            $this->getCancelFormAction(),
            $this->getResetFormAction(),
            $this->getDeleteFormAction()
                ->extraAttributes([
                    'class' => ' md:ml-auto md:w-auto ',
                ]),
        ];
    }

    protected function getDeleteFormAction()
    {
        return DeleteAction::make()
            ->visible(function ($record) {
                return Auth::user()->hasRole('super_admin') ||
                       Auth::user()->scopeTypes
                           ->where('id', $record->scope_type_id)
                           ->first()
                           ->pivot
                           ->permission === Permission::DELETE->value;
            });
    }

    protected function getCancelFormAction(): Actions\Action
    {
        return Actions\Action::make('cancel')
            ->label('Indietro')
            ->color('gray')
            ->url(function () {
                if ($this->previousUrl && str($this->previousUrl)->contains('/deadlines?')) {
                    return $this->previousUrl;
                }
                return DeadlineResource::getUrl('index');
            });
    }

    protected function getResetFormAction(): Actions\Action
    {
        return Actions\Action::make('reset')
            ->label('Annulla')
            ->color('gray')
            ->action(function () {
                $this->data = $this->getRecord()->toArray();
                $this->fillForm();
            });
    }
}
