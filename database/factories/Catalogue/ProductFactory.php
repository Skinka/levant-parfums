<?php

namespace Database\Factories\Catalogue;

use App\Enums\Gender;
use App\Models\Catalogue\Brand;
use App\Models\Catalogue\Concentration;
use App\Models\Catalogue\PerfumeFamily;
use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = 'LUXURY '.fake()->unique()->numberBetween(1, 9999);

        return [
            'sku' => 'LV-'.fake()->unique()->numerify('######'),
            'slug' => Str::slug($name),
            'name' => ['uk' => $name, 'en' => $name],
            'tagline' => ['uk' => fake('uk_UA')->sentence(4), 'en' => fake()->sentence(4)],
            'description' => ['uk' => fake('uk_UA')->paragraph(), 'en' => fake()->paragraph()],
            'inspired_perfume_name' => fake()->words(2, true),
            'inspired_brand_id' => Brand::factory(),
            'volume_ml' => 50,
            'gender' => fake()->randomElement(Gender::cases())->value,
            'price_uah' => fake()->randomFloat(2, 500, 5000),
            'price_eur' => fake()->randomFloat(2, 15, 130),
            'in_stock' => true,
            'is_published' => true,
            'published_at' => now(),
            'seo_title' => ['uk' => $name, 'en' => $name],
            'seo_description' => ['uk' => fake('uk_UA')->sentence(), 'en' => fake()->sentence()],
            'perfume_family_id' => PerfumeFamily::factory(),
            'concentration_id' => Concentration::factory(),
            'series_id' => Series::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['is_published' => false, 'published_at' => null]);
    }

    public function outOfStock(): static
    {
        return $this->state(['in_stock' => false]);
    }
}
