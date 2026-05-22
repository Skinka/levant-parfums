<?php

namespace Database\Factories\Catalogue;

use App\Models\Catalogue\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => ['uk' => $name, 'en' => $name],
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'country' => fake()->countryCode(),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
