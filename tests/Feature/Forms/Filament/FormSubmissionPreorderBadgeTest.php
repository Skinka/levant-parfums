<?php

use App\Filament\Resources\FormSubmissions\Pages\ListFormSubmissions;
use App\Forms\Models\FormSubmission;
use App\Models\Catalogue\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('preorder column shows PRE-ORDER badge for preorder submissions', function () {
    $p = Product::factory()->create();
    $order = FormSubmission::create([
        'type' => 'order', 'status' => 'new', 'data' => ['is_preorder' => false],
        'subject_type' => $p->getMorphClass(), 'subject_id' => $p->id,
    ]);
    $preorder = FormSubmission::create([
        'type' => 'order', 'status' => 'new', 'data' => ['is_preorder' => true],
        'subject_type' => $p->getMorphClass(), 'subject_id' => $p->id,
    ]);

    Livewire::test(ListFormSubmissions::class)
        ->assertCanSeeTableRecords([$order, $preorder])
        ->assertTableColumnExists('preorder');
});
