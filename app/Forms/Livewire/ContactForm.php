<?php

namespace App\Forms\Livewire;

use App\Forms\Types\ContactFormType;
use App\Forms\Types\FormType;
use Illuminate\Contracts\View\View;

class ContactForm extends FormComponent
{
    public string $name = '';
    public string $email = '';
    public string $message = '';

    protected function formType(): FormType
    {
        return app(ContactFormType::class);
    }

    public function render(): View
    {
        return view('forms.contact');
    }
}
