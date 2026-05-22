<?php

namespace Database\Factories\Catalogue;

use App\Models\Catalogue\Season;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Season>
 */
class SeasonFactory extends Factory
{
    protected $model = Season::class;

    public function definition(): array
    {
        $uk = fake('uk_UA')->unique()->word();

        return [
            'name' => ['uk' => $uk, 'en' => Str::title($uk)],
            'slug' => Str::slug($uk).'-'.fake()->unique()->numberBetween(1, 99999),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
