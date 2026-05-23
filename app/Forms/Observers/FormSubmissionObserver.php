<?php

namespace App\Forms\Observers;

use App\Filament\Resources\FormSubmissions\FormSubmissionResource;
use App\Forms\Models\FormSubmission;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class FormSubmissionObserver
{
    public function created(FormSubmission $submission): void
    {
        $title = trans('forms.notifications.new', [
            'type' => trans("forms.types.{$submission->type}"),
        ]);

        $url = class_exists(FormSubmissionResource::class)
            ? FormSubmissionResource::getUrl('view', ['record' => $submission])
            : null;

        $notification = Notification::make()
            ->title($title)
            ->icon('heroicon-o-inbox-arrow-down');

        if ($url !== null) {
            $notification->actions([
                Action::make('view')->url($url),
            ]);
        }

        $notification->sendToDatabase(User::all());
    }
}
