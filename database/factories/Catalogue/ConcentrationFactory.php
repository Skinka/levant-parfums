<?php

namespace Database\Factories\Catalogue;

use App\Models\Catalogue\Concentration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Concentration>
 */
class ConcentrationFactory extends Factory
{
    protected $model = Concentration::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();
        $abbr = strtoupper(Str::substr($name, 0, 3));

        return [
            'name' => ['uk' => "EDP $name", 'en' => "EDP $name"],
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'abbreviation' => $abbr,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
