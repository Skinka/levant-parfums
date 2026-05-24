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

function validOrderPayload(): array
{
    return [
        'name' => 'Iван',
        'phone' => '+380501234567',
        'email' => 'ivan@example.test',
        'city' => 'Київ',
        'np_office' => 'Відділення №12',
        'qty' => 2,
        'comment' => 'Дзвоніть після 18:00',
    ];
}

it('mount without subject throws', function () {
    expect(fn () => Livewire::test(OrderForm::class))->toThrow(LogicException::class);
});

it('mount with non-Product subject throws', function () {
    $article = Article::factory()->create();
    expect(fn () => Livewire::test(OrderForm::class, ['subject' => $article]))->toThrow(LogicException::class);
});

it('valid submit on in-stock product persists with is_preorder=false', function () {
    $product = Product::factory()->create(['in_stock' => true]);

    Livewire::test(OrderForm::class, ['subject' => $product])
        ->set(validOrderPayload())
        ->call('submit')
        ->assertHasNoErrors()
        ->assertDispatched('form-submitted');

    $row = FormSubmission::first();
    expect($row->data)->toMatchArray(validOrderPayload() + ['is_preorder' => false]);

    Mail::assertQueued(OrderAdminMail::class);
    Mail::assertQueued(OrderClientMail::class);
});

it('valid submit on out-of-stock product persists with is_preorder=true', function () {
    $product = Product::factory()->create(['in_stock' => false]);

    Livewire::test(OrderForm::class, ['subject' => $product])
        ->set(validOrderPayload())
        ->call('submit')
        ->assertHasNoErrors();

    $row = FormSubmission::first();
    expect($row->data['is_preorder'])->toBeTrue();
});

it('rejects missing required city', function () {
    $product = Product::factory()->create();
    $payload = validOrderPayload();
    $payload['city'] = '';

    Livewire::test(OrderForm::class, ['subject' => $product])
        ->set($payload)
        ->call('submit')
        ->assertHasErrors(['city']);
});

it('rejects missing required np_office', function () {
    $product = Product::factory()->create();
    $payload = validOrderPayload();
    $payload['np_office'] = '';

    Livewire::test(OrderForm::class, ['subject' => $product])
        ->set($payload)
        ->call('submit')
        ->assertHasErrors(['np_office']);
});

it('qty must be 1..5 inclusive', function () {
    $product = Product::factory()->create();

    Livewire::test(OrderForm::class, ['subject' => $product])
        ->set(validOrderPayload())->set('qty', 0)->call('submit')->assertHasErrors(['qty']);

    Livewire::test(OrderForm::class, ['subject' => $product])
        ->set(validOrderPayload())->set('qty', 6)->call('submit')->assertHasErrors(['qty']);
});

it('increment() and decrement() clamp qty to 1..5', function () {
    $product = Product::factory()->create();

    Livewire::test(OrderForm::class, ['subject' => $product])
        ->assertSet('qty', 1)
        ->call('decrement')->assertSet('qty', 1)
        ->call('increment')->assertSet('qty', 2)
        ->call('increment')->call('increment')->call('increment')->call('increment')
        ->assertSet('qty', 5);
});

it('admin mail subject differs for order vs preorder', function () {
    config()->set('app.fallback_locale', 'uk');

    $inStock = Product::factory()->create(['in_stock' => true]);
    Livewire::test(OrderForm::class, ['subject' => $inStock])
        ->set(validOrderPayload())->call('submit');

    Mail::assertQueued(OrderAdminMail::class, function ($mail) {
        return str_contains($mail->envelope()->subject, 'замовлення')
            && ! str_contains($mail->envelope()->subject, 'Передзамовлення');
    });

    Mail::fake();
    RateLimiter::clear('forms:order:127.0.0.1');

    $outOfStock = Product::factory()->create(['in_stock' => false]);
    Livewire::test(OrderForm::class, ['subject' => $outOfStock])
        ->set(validOrderPayload())->call('submit');

    Mail::assertQueued(OrderAdminMail::class, function ($mail) {
        return str_contains($mail->envelope()->subject, 'передзамовлення');
    });
});
