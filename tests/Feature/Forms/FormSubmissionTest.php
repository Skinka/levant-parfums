<?php

use App\Forms\Models\FormSubmission;
use App\Models\Catalogue\Product;

it('persists data, meta, locale and timestamps', function () {
    $submission = FormSubmission::factory()->create([
        'data' => ['name' => 'Іван', 'email' => 'ivan@example.test'],
        'meta' => ['ip' => '203.0.113.5'],
        'locale' => 'uk',
    ]);

    $fresh = $submission->fresh();
    expect($fresh->data)->toBe(['name' => 'Іван', 'email' => 'ivan@example.test']);
    expect($fresh->meta)->toBe(['ip' => '203.0.113.5']);
    expect($fresh->locale)->toBe('uk');
    expect($fresh->created_at)->not->toBeNull();
});

it('defaults status to new', function () {
    $submission = FormSubmission::create([
        'type' => 'contact',
        'data' => ['name' => 'X'],
    ]);

    expect($submission->fresh()->status)->toBe(FormSubmission::STATUS_NEW);
});

it('resolves polymorphic subject to Product', function () {
    $product = Product::factory()->create();

    $submission = FormSubmission::factory()->order()->create([
        'subject_type' => $product->getMorphClass(),
        'subject_id' => $product->getKey(),
    ]);

    expect($submission->subject)->toBeInstanceOf(Product::class);
    expect($submission->subject->is($product))->toBeTrue();
});

it('markProcessed sets status and handled_at', function () {
    $submission = FormSubmission::factory()->create();

    $submission->markProcessed();

    expect($submission->fresh()->status)->toBe(FormSubmission::STATUS_PROCESSED);
    expect($submission->fresh()->handled_at)->not->toBeNull();
});

it('markNew clears handled_at and resets status', function () {
    $submission = FormSubmission::factory()->status(FormSubmission::STATUS_PROCESSED)->create();
    expect($submission->handled_at)->not->toBeNull();

    $submission->markNew();

    expect($submission->fresh()->status)->toBe(FormSubmission::STATUS_NEW);
    expect($submission->fresh()->handled_at)->toBeNull();
});
