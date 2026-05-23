<?php

use App\Filament\Resources\FormSubmissions\FormSubmissionResource;
use App\Filament\Resources\FormSubmissions\Pages\ListFormSubmissions;
use App\Filament\Resources\FormSubmissions\Pages\ViewFormSubmission;
use App\Forms\Models\FormSubmission;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('list page renders', function () {
    FormSubmission::factory()->count(3)->create();

    Livewire::test(ListFormSubmissions::class)
        ->assertOk()
        ->assertCanSeeTableRecords(FormSubmission::all());
});

it('resource exposes only index and view pages', function () {
    $pages = FormSubmissionResource::getPages();
    expect(array_keys($pages))->toBe(['index', 'view']);
});

it('view page renders for a record', function () {
    $row = FormSubmission::factory()->create();

    Livewire::test(ViewFormSubmission::class, ['record' => $row->getKey()])
        ->assertOk();
});

it('mark_read row action transitions new -> read', function () {
    $row = FormSubmission::factory()->create(['status' => FormSubmission::STATUS_NEW]);

    Livewire::test(ListFormSubmissions::class)
        ->callTableAction('mark_read', $row);

    expect($row->fresh()->status)->toBe(FormSubmission::STATUS_READ);
});

it('mark_processed sets status and handled_at', function () {
    $row = FormSubmission::factory()->create(['status' => FormSubmission::STATUS_READ, 'handled_at' => null]);

    Livewire::test(ListFormSubmissions::class)
        ->callTableAction('mark_processed', $row);

    $fresh = $row->fresh();
    expect($fresh->status)->toBe(FormSubmission::STATUS_PROCESSED);
    expect($fresh->handled_at)->not->toBeNull();
});

it('mark_new reverts status and clears handled_at', function () {
    $row = FormSubmission::factory()->state(['status' => FormSubmission::STATUS_PROCESSED, 'handled_at' => now()])->create();

    Livewire::test(ListFormSubmissions::class)
        ->callTableAction('mark_new', $row);

    $fresh = $row->fresh();
    expect($fresh->status)->toBe(FormSubmission::STATUS_NEW);
    expect($fresh->handled_at)->toBeNull();
});

it('filter by type narrows the list', function () {
    FormSubmission::factory()->count(2)->create(['type' => 'contact']);
    FormSubmission::factory()->count(3)->order()->create(['type' => 'order']);

    Livewire::test(ListFormSubmissions::class)
        ->filterTable('type', 'order')
        ->assertCanSeeTableRecords(FormSubmission::query()->where('type', 'order')->get())
        ->assertCanNotSeeTableRecords(FormSubmission::query()->where('type', 'contact')->get());
});

it('navigation badge equals count of new submissions', function () {
    FormSubmission::factory()->count(2)->create(['status' => FormSubmission::STATUS_NEW]);
    FormSubmission::factory()->count(3)->create(['status' => FormSubmission::STATUS_READ]);

    expect(FormSubmissionResource::getNavigationBadge())->toBe('2');
});
