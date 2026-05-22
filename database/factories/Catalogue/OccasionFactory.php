<?php

namespace Database\Factories\Catalogue;

use App\Models\Catalogue\Occasion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Occasion>
 */
class OccasionFactory extends Factory
{
    protected $model = Occasion::class;

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
