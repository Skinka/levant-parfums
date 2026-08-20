<?php

namespace App\Providers;

use App\Forms\Livewire\ContactForm;
use App\Forms\Livewire\OrderForm;
use App\Forms\Models\FormSubmission;
use App\Forms\Observers\FormSubmissionObserver;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Mcamara\LaravelLocalization\Traits\LoadsTranslatedCachedRoutes;

class AppServiceProvider extends ServiceProvider
{
    use LoadsTranslatedCachedRoutes;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RouteServiceProvider::loadCachedRoutesUsing(fn () => $this->loadCachedRoutes());

        FormSubmission::observe(FormSubmissionObserver::class);

        Livewire::component('contact-form', ContactForm::class);
        Livewire::component('order-form', OrderForm::class);
    }
}
