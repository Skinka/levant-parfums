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
        $uk = $this->faker->unique()->word();

        return [
            'name' => ['uk' => $uk, 'en' => Str::title($uk)],
            'slug' => Str::slug($uk).'-'.$this->faker->unique()->numberBetween(1, 99999),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
