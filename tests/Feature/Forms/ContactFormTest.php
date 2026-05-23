<?php

use App\Forms\Livewire\ContactForm;
use App\Forms\Mail\ContactAdminMail;
use App\Forms\Models\FormSubmission;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('forms.admin_email', 'admin@levantparfums.test');
    Mail::fake();
    RateLimiter::clear('forms:contact:127.0.0.1');
});

it('valid submit creates row, queues admin mail, flashes success', function () {
    Livewire::test(ContactForm::class)
        ->set('name', 'Iван')
        ->set('email', 'ivan@example.test')
        ->set('message', 'Привiт')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertDispatched('form-submitted');

    expect(FormSubmission::count())->toBe(1);
    $row = FormSubmission::first();
    expect($row->type)->toBe('contact');
    expect($row->data)->toMatchArray([
        'name' => 'Iван',
        'email' => 'ivan@example.test',
        'message' => 'Привiт',
    ]);
    expect($row->subject_type)->toBeNull();
    expect($row->subject_id)->toBeNull();

    Mail::assertQueued(ContactAdminMail::class, fn ($m) => $m->hasTo('admin@levantparfums.test')
        && $m->submission->is($row));
});

it('invalid email surfaces inline validation error and does not persist', function () {
    Livewire::test(ContactForm::class)
        ->set('name', 'Iван')
        ->set('email', 'not-an-email')
        ->set('message', 'Привiт')
        ->call('submit')
        ->assertHasErrors(['email']);

    expect(FormSubmission::count())->toBe(0);
    Mail::assertNothingQueued();
});

it('honeypot tripped: no row, no mail, no errors', function () {
    Livewire::test(ContactForm::class)
        ->set('name', 'Iван')
        ->set('email', 'ivan@example.test')
        ->set('message', 'Привiт')
        ->set('hp', 'bot-filled-this')
        ->call('submit')
        ->assertHasNoErrors();

    expect(FormSubmission::count())->toBe(0);
    Mail::assertNothingQueued();
});

it('6th submit within window throws rate-limit error', function () {
    for ($i = 0; $i < 5; $i++) {
        Livewire::test(ContactForm::class)
            ->set('name', "User {$i}")
            ->set('email', "u{$i}@example.test")
            ->set('message', 'Hi')
            ->call('submit')
            ->assertHasNoErrors();
    }

    Livewire::test(ContactForm::class)
        ->set('name', 'Sixth')
        ->set('email', 'sixth@example.test')
        ->set('message', 'Hi')
        ->call('submit')
        ->assertHasErrors(['form']);

    expect(FormSubmission::count())->toBe(5);
});

it('locale is captured from current LaravelLocalization locale', function () {
    app(\Mcamara\LaravelLocalization\LaravelLocalization::class)->setLocale('en');

    Livewire::test(ContactForm::class)
        ->set('name', 'John')
        ->set('email', 'john@example.test')
        ->set('message', 'Hi')
        ->call('submit')
        ->assertHasNoErrors();

    expect(FormSubmission::first()->locale)->toBe('en');
});
