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
    public string $city = '';
    public string $np_office = '';
    public int $qty = 1;
    public string $comment = '';

    protected function formType(): FormType
    {
        return app(OrderFormType::class);
    }

    public function increment(): void
    {
        $this->qty = min(5, $this->qty + 1);
    }

    public function decrement(): void
    {
        $this->qty = max(1, $this->qty - 1);
    }

    public function getSubtotalProperty(): float
    {
        if (! $this->subject) {
            return 0;
        }

        return (float) $this->subject->displayPrice()['amount'] * $this->qty;
    }

    public function render(): View
    {
        return view('forms.order');
    }
}
