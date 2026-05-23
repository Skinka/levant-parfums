<?php

namespace Database\Seeders\Forms;

use App\Forms\Models\FormSubmission;
use App\Models\Catalogue\Product;
use Illuminate\Database\Seeder;

class FormSubmissionSeeder extends Seeder
{
    public function run(): void
    {
        // 5 contact submissions: mix of statuses
        FormSubmission::factory()->count(2)->create();
        FormSubmission::factory()->status(FormSubmission::STATUS_READ)->count(2)->create();
        FormSubmission::factory()->status(FormSubmission::STATUS_PROCESSED)->count(1)->create();

        // 5 order submissions tied to real products (half new, half processed)
        $products = Product::query()->inRandomOrder()->limit(5)->get();
        if ($products->isEmpty()) {
            return;
        }

        foreach ($products as $i => $product) {
            $status = $i < 3 ? FormSubmission::STATUS_NEW : FormSubmission::STATUS_PROCESSED;
            FormSubmission::factory()
                ->order()
                ->status($status)
                ->create([
                    'subject_type' => $product->getMorphClass(),
                    'subject_id' => $product->getKey(),
                ]);
        }
    }
}
