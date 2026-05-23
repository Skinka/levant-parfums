<?php

namespace App\Forms\Livewire;

use App\Forms\Types\FormType;
use App\Forms\Types\OrderFormType;
use Illuminate\Contracts\View\View;

class OrderForm extends FormComponent
{
    public string $name = '';
    public string $phone = '';
    public string $email = '';
    public int $qty = 1;
    public string $note = '';

    protected function formType(): FormType
    {
        return app(OrderFormType::class);
    }

    public function render(): View
    {
        return view('forms.order');
    }
}
