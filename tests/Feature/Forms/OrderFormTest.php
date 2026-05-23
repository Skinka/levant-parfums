<?php

use App\Forms\Livewire\OrderForm;
use App\Forms\Mail\OrderAdminMail;
use App\Forms\Mail\OrderClientMail;
use App\Forms\Models\FormSubmission;
use App\Models\Catalogue\Product;
use App\Models\Content\Article;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('forms.admin_email', 'admin@levantparfums.test');
    Mail::fake();
    RateLimiter::clear('forms:order:127.0.0.1');
});

it('mount without subject throws LogicException', function () {
    expect(fn () => Livewire::test(OrderForm::class))
        ->toThrow(LogicException::class);
});

it('mount with non-Product subject throws LogicException', function () {
    $article = Article::factory()->create();

    expect(fn () => Livewire::test(OrderForm::class, ['subject' => $article]))
        ->toThrow(LogicException::class);
});

it('valid submit persists subject morph and queues admin + client mails', function () {
    $product = Product::factory()->create();

    Livewire::test(OrderForm::class, ['subject' => $product])
        ->set('name', 'Iван')
        ->set('phone', '+380501234567')
        ->set('email', 'ivan@example.test')
        ->set('qty', 2)
        ->set('note', 'Подзвонiть пiсля 18:00')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertDispatched('form-submitted');

    $row = FormSubmission::first();
    expect($row->type)->toBe('order');
    expect($row->subject_type)->toBe($product->getMorphClass());
    expect((int) $row->subject_id)->toBe($product->getKey());
    expect($row->data)->toMatchArray([
        'name' => 'Iван',
        'phone' => '+380501234567',
        'email' => 'ivan@example.test',
        'qty' => 2,
    ]);

    Mail::assertQueued(OrderAdminMail::class, fn ($m) => $m->hasTo('admin@levantparfums.test'));
    Mail::assertQueued(OrderClientMail::class, fn ($m) => $m->hasTo('ivan@example.test'));
});

it('blank email blocks submission and queues no mail', function () {
    $product = Product::factory()->create();

    Livewire::test(OrderForm::class, ['subject' => $product])
        ->set('name', 'Iван')
        ->set('phone', '+380501234567')
        ->set('email', '')
        ->set('qty', 2)
        ->call('submit')
        ->assertHasErrors(['email']);

    expect(FormSubmission::count())->toBe(0);
    Mail::assertNothingQueued();
});

it('ContactForm (clientMailable=null) emits exactly one mail — covers the null-clientMail branch', function () {
    // Cross-check that the base dispatch logic differentiates client-mail null vs. set.
    \Illuminate\Support\Facades\RateLimiter::clear('forms:contact:127.0.0.1');

    Livewire::test(\App\Forms\Livewire\ContactForm::class)
        ->set('name', 'X')
        ->set('email', 'x@example.test')
        ->set('message', 'hi')
        ->call('submit')
        ->assertHasNoErrors();

    Mail::assertQueuedCount(1);
});

it('qty must be a positive integer', function () {
    $product = Product::factory()->create();

    Livewire::test(OrderForm::class, ['subject' => $product])
        ->set('name', 'Iван')
        ->set('phone', '+380501234567')
        ->set('email', 'ivan@example.test')
        ->set('qty', 0)
        ->call('submit')
        ->assertHasErrors(['qty']);
});
