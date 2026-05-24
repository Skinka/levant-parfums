<?php

namespace App\Forms\Types;

use App\Forms\Mail\OrderAdminMail;
use App\Forms\Mail\OrderClientMail;
use App\Forms\Models\FormSubmission;
use App\Models\Catalogue\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;

class OrderFormType extends FormType
{
    public function key(): string
    {
        return 'order';
    }

    public function label(): string
    {
        return trans('forms.types.order');
    }

    public function subjectClass(): ?string
    {
        return Product::class;
    }

    public function subjectRequired(): bool
    {
        return true;
    }

    public function rules(?Model $subject = null): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'np_office' => ['required', 'string', 'max:80'],
            'qty' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => trans('forms.fields.name'),
            'phone' => trans('forms.fields.phone'),
            'email' => trans('forms.fields.email'),
            'city' => trans('forms.fields.city'),
            'np_office' => trans('forms.fields.np_office'),
            'qty' => trans('forms.fields.qty'),
            'comment' => trans('forms.fields.comment'),
        ];
    }

    public function adminMailable(FormSubmission $submission): Mailable
    {
        return new OrderAdminMail($submission);
    }

    public function clientMailable(FormSubmission $submission): ?Mailable
    {
        return new OrderClientMail($submission);
    }

    public function metadata(?Model $subject): array
    {
        return [
            'is_preorder' => $subject instanceof Product ? ! $subject->in_stock : false,
        ];
    }
}
