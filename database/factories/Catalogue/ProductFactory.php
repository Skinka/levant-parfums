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
        $name = 'LUXURY '.$this->faker->unique()->numberBetween(1, 9999);

        return [
            'sku' => 'LV-'.$this->faker->unique()->numerify('######'),
            'slug' => Str::slug($name),
            'name' => ['uk' => $name, 'en' => $name],
            'tagline' => ['uk' => $this->faker->sentence(4), 'en' => $this->faker->sentence(4)],
            'description' => ['uk' => $this->faker->paragraph(), 'en' => $this->faker->paragraph()],
            'inspired_perfume_name' => $this->faker->words(2, true),
            'inspired_brand_id' => Brand::factory(),
            'volume_ml' => 50,
            'gender' => $this->faker->randomElement(Gender::cases())->value,
            'price_uah' => $this->faker->randomFloat(2, 500, 5000),
            'price_eur' => $this->faker->randomFloat(2, 15, 130),
            'in_stock' => true,
            'is_published' => true,
            'published_at' => now(),
            'seo_title' => ['uk' => $name, 'en' => $name],
            'seo_description' => ['uk' => $this->faker->sentence(), 'en' => $this->faker->sentence()],
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
