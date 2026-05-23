<?php

namespace App\Forms\Types;

use App\Forms\Mail\ContactAdminMail;
use App\Forms\Models\FormSubmission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;

class ContactFormType extends FormType
{
    public function key(): string
    {
        return 'contact';
    }

    public function label(): string
    {
        return trans('forms.types.contact');
    }

    public function rules(?Model $subject = null): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => trans('forms.fields.name'),
            'email' => trans('forms.fields.email'),
            'message' => trans('forms.fields.message'),
        ];
    }

    public function adminMailable(FormSubmission $submission): Mailable
    {
        return new ContactAdminMail($submission);
    }
}
