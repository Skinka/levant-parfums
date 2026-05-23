<?php

namespace Database\Seeders\Content;

use App\Models\Catalogue\Product;
use App\Models\Content\Article;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::query()->inRandomOrder()->limit(9)->get();
        if ($products->count() < 3) {
            return;
        }

        $chunks = $products->chunk(3)->values();

        Article::factory()->count(3)->create()->each(function (Article $article, int $i) use ($chunks) {
            $set = $chunks[$i] ?? collect();
            foreach ($set->values() as $idx => $product) {
                $article->products()->attach($product->id, ['sort_order' => $idx]);
            }
        });
    }
}
