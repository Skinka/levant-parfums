<?php

use Illuminate\Support\Facades\DB;

it('serves the home page', function () {
    $response = $this->get('/');

    expect($response->getStatusCode())->toBeIn([200, 302]);
});

it('serves the filament login', function () {
    $this->get('/admin/login')->assertOk();
});

it('connects to the database', function () {
    expect(DB::connection()->getPdo())->not->toBeNull();
});
