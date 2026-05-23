<?php

namespace App\Providers;

use App\Forms\Livewire\ContactForm;
use App\Forms\Livewire\OrderForm;
use App\Forms\Models\FormSubmission;
use App\Forms\Observers\FormSubmissionObserver;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        FormSubmission::observe(FormSubmissionObserver::class);

        Livewire::component('contact-form', ContactForm::class);
        Livewire::component('order-form', OrderForm::class);
    }
}
