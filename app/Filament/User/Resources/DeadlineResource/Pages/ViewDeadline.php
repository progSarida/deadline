<?php

namespace App\Filament\User\Resources\DeadlineResource\Pages;

use App\Filament\User\Resources\DeadlineResource;
use App\Models\Deadline;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewDeadline extends ViewRecord
{
    protected static string $resource = DeadlineResource::class;

    public function getTitle(): string | Htmlable
    {
        return $this->record->description;
    }

    protected function getHeaderActions(): array
    {
        $current = $this->record;
        $previous = Deadline::where('deadline_date', '<', $current->deadline_date)->orderBy('deadline_date', 'desc')->first();
        $next = Deadline::where('deadline_date', '>', $current->deadline_date)->orderBy('deadline_date', 'asc')->first();
        return [
            Actions\Action::make('back')
                ->label('Indietro')
                ->url($this->getResource()::getUrl('index'))
                ->color('gray'),
            // Scorrimento
            Actions\Action::make('previous_doc')
                ->label('Precedente')
                ->color('info')
                ->icon('heroicon-o-arrow-left-circle')
                ->visible(function () use ($previous) { return $previous;})
                ->action(function () use ($previous) {
                    $this->redirect(DeadlineResource::getUrl('view', ['record' => $previous->id]));
                }),
            Actions\Action::make('next_doc')
                ->label('Successiva')
                ->color('info')
                ->icon('heroicon-o-arrow-right-circle')
                ->visible(function () use ($next) { return $next;})
                ->action(function () use ($next) {
                    $this->redirect(DeadlineResource::getUrl('view', ['record' => $next->id]));
                }),
            Actions\EditAction::make(),
        ];
    }
}
