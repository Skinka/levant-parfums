<?php

use App\Forms\Mail\ContactAdminMail;
use App\Forms\Mail\OrderAdminMail;
use App\Forms\Mail\OrderClientMail;
use App\Forms\Models\FormSubmission;
use App\Forms\Types\ContactFormType;
use App\Forms\Types\OrderFormType;
use App\Models\Catalogue\Product;

it('ContactFormType: key + label + rules + admin mailable', function () {
    config()->set('forms.admin_email', 'admin@levantparfums.test');
    $type = new ContactFormType;

    expect($type->key())->toBe('contact');
    expect($type->label())->not->toBeEmpty();
    expect($type->subjectRequired())->toBeFalse();
    expect($type->subjectClass())->toBeNull();
    expect($type->rules())->toHaveKeys(['name', 'email', 'message']);
    expect($type->adminRecipients())->toBe(['admin@levantparfums.test']);

    $submission = FormSubmission::factory()->make();
    expect($type->adminMailable($submission))->toBeInstanceOf(ContactAdminMail::class);
    expect($type->clientMailable($submission))->toBeNull();
});

it('OrderFormType: requires Product subject and emits admin + client mailables', function () {
    config()->set('forms.admin_email', 'admin@levantparfums.test');
    $type = new OrderFormType;

    expect($type->key())->toBe('order');
    expect($type->subjectRequired())->toBeTrue();
    expect($type->subjectClass())->toBe(Product::class);
    expect($type->rules())->toHaveKeys(['name', 'phone', 'email', 'qty']);

    $submission = FormSubmission::factory()->order()->make();
    expect($type->adminMailable($submission))->toBeInstanceOf(OrderAdminMail::class);
    expect($type->clientMailable($submission))->toBeInstanceOf(OrderClientMail::class);
});

it('adminRecipients filters out null FORMS_ADMIN_EMAIL', function () {
    config()->set('forms.admin_email', null);
    expect((new ContactFormType)->adminRecipients())->toBe([]);
});

it('rateLimit default is 5 attempts per 60 minutes', function () {
    expect((new ContactFormType)->rateLimit())->toBe([5, 60]);
});

it('FormType::metadata() default returns empty array', function () {
    $type = new class extends \App\Forms\Types\FormType {
        public function key(): string { return 'x'; }
        public function label(): string { return 'X'; }
        public function rules(?\Illuminate\Database\Eloquent\Model $s = null): array { return []; }
        public function adminMailable(\App\Forms\Models\FormSubmission $s): \Illuminate\Mail\Mailable {
            return new class extends \Illuminate\Mail\Mailable {};
        }
    };
    expect($type->metadata(null))->toBe([]);
});

it('OrderFormType::metadata() returns is_preorder = false for in_stock product', function () {
    $p = Product::factory()->create(['in_stock' => true]);
    expect(app(OrderFormType::class)->metadata($p))
        ->toBe(['is_preorder' => false]);
});

it('OrderFormType::metadata() returns is_preorder = true for out-of-stock product', function () {
    $p = Product::factory()->create(['in_stock' => false]);
    expect(app(OrderFormType::class)->metadata($p))
        ->toBe(['is_preorder' => true]);
});

it('OrderFormType::metadata() returns is_preorder = false when subject is null', function () {
    expect(app(OrderFormType::class)->metadata(null))
        ->toBe(['is_preorder' => false]);
});
