<?php

use App\Forms\Models\FormSubmission;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

beforeEach(function () {
    User::factory()->create(['email' => 'admin1@levantparfums.test']);
    User::factory()->create(['email' => 'admin2@levantparfums.test']);
});

it('creating a submission sends a database notification to every user', function () {
    expect(DatabaseNotification::count())->toBe(0);

    FormSubmission::factory()->create(['type' => 'contact']);

    expect(DatabaseNotification::count())->toBe(2);
    expect(DatabaseNotification::query()->pluck('notifiable_id')->sort()->values()->toArray())
        ->toBe(User::query()->orderBy('id')->pluck('id')->toArray());
});

it('notification title includes the translated type label', function () {
    FormSubmission::factory()->create(['type' => 'order']);

    $payload = DatabaseNotification::first()->data;
    expect($payload['title'])->toContain(trans('forms.types.order'));
});
