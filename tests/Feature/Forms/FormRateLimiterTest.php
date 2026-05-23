<?php

use App\Forms\Support\FormRateLimiter;
use App\Forms\Types\ContactFormType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    RateLimiter::clear('forms:contact:127.0.0.1');
});

it('allows up to N attempts and throws on N+1', function () {
    $type = new ContactFormType;
    $request = Request::create('/', server: ['REMOTE_ADDR' => '127.0.0.1']);

    for ($i = 0; $i < 5; $i++) {
        FormRateLimiter::ensureAllowed($type, $request);
    }

    expect(fn () => FormRateLimiter::ensureAllowed($type, $request))
        ->toThrow(ValidationException::class);
});

it('uses translated message key forms.errors.rate_limited', function () {
    $type = new ContactFormType;
    $request = Request::create('/', server: ['REMOTE_ADDR' => '127.0.0.1']);

    for ($i = 0; $i < 5; $i++) {
        FormRateLimiter::ensureAllowed($type, $request);
    }

    try {
        FormRateLimiter::ensureAllowed($type, $request);
        expect(false)->toBeTrue('should have thrown');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('form');
        expect($e->errors()['form'][0])->toBe(trans('forms.errors.rate_limited'));
    }
});

it('keys are isolated per IP', function () {
    $type = new ContactFormType;
    $ip1 = Request::create('/', server: ['REMOTE_ADDR' => '10.0.0.1']);
    $ip2 = Request::create('/', server: ['REMOTE_ADDR' => '10.0.0.2']);

    for ($i = 0; $i < 5; $i++) {
        FormRateLimiter::ensureAllowed($type, $ip1);
    }

    // Second IP still allowed.
    FormRateLimiter::ensureAllowed($type, $ip2);
    expect(true)->toBeTrue();

    RateLimiter::clear('forms:contact:10.0.0.1');
    RateLimiter::clear('forms:contact:10.0.0.2');
});
