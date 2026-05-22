<?php

namespace Database\Factories\Catalogue;

use App\Models\Catalogue\PerfumeFamily;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PerfumeFamily>
 */
class PerfumeFamilyFactory extends Factory
{
    protected $model = PerfumeFamily::class;

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
