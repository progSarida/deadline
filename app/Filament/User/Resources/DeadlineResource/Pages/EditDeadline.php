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
use Illuminate\Support\Facades\Auth;

class EditDeadline extends EditRecord
{
    protected static string $resource = DeadlineResource::class;

    protected function getHeaderActions(): array
    {
        $currentDeadline = $this->record;
        $defaultDate = $this->calculateDefaultDate($currentDeadline);
        return [
            // DeleteAction::make()
            //     ->visible(function ($record) {
            //         // return Auth::user()->is_admin || Auth::user()->scopeTypes->where('id', $record->scope_type_id)->first()->pivot->permission === Permission::DELETE->value;
            //         return Auth::user()->hasRole('super_admin') || Auth::user()->scopeTypes->where('id', $record->scope_type_id)->first()->pivot->permission === Permission::DELETE->value;
            //     }),
            Action::make('renew_deadline')
                ->label('Rinnovo scadenza')
                ->visible(fn () => ($currentDeadline->recurrent && $currentDeadline->met && !$currentDeadline->renew))
                ->modalHeading('Rinnovo Scadenza')
                ->modalWidth('xs')
                ->form([
                    DatePicker::make('new_deadline_date')
                        ->label('Nuova data scadenza')
                        ->extraInputAttributes(['class' => 'text-center'])
                        ->required()
                        ->default($defaultDate),
                ])
                ->action(function (array $data) use ($currentDeadline) {
                    try {
                        Deadline::create([
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

                        $currentDeadline->update(['renew' => true]);

                        Notification::make('success')
                            ->title('Scadenza rinnovata con successo!')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make('error')
                            ->title('Errore durante il rinnovo della scadenza: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    // calcolo la data della nuova scadenza periodica
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

    protected function afterSave(): void
    {
        $this->refreshFormData(['updated_at', 'modify_user_id']);                   // ricarico i campi specificati
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()->color('success'),
            $this->getCancelFormAction(),
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
                    // return Auth::user()->is_admin || Auth::user()->scopeTypes->where('id', $record->scope_type_id)->first()->pivot->permission === Permission::DELETE->value;
                    return Auth::user()->hasRole('super_admin') || Auth::user()->scopeTypes->where('id', $record->scope_type_id)->first()->pivot->permission === Permission::DELETE->value;
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
}
