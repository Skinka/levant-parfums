<?php

namespace App\Filament\Resources\FormSubmissions\Pages;

use App\Filament\Resources\FormSubmissions\FormSubmissionResource;
use App\Forms\Models\FormSubmission;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewFormSubmission extends ViewRecord
{
    protected static string $resource = FormSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        /** @var FormSubmission $record */
        $record = $this->record;

        return [
            Action::make('mark_read')
                ->label(trans('forms.actions.mark_read'))
                ->visible(fn () => $record->status === FormSubmission::STATUS_NEW)
                ->action(fn () => $record->markRead()),

            Action::make('mark_processed')
                ->label(trans('forms.actions.mark_processed'))
                ->visible(fn () => $record->status !== FormSubmission::STATUS_PROCESSED)
                ->action(fn () => $record->markProcessed()),

            Action::make('mark_new')
                ->label(trans('forms.actions.mark_new'))
                ->visible(fn () => $record->status !== FormSubmission::STATUS_NEW)
                ->action(fn () => $record->markNew()),
        ];
    }
}
