<?php

namespace Database\Factories\Catalogue;

use App\Models\Catalogue\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ['uk' => $name, 'en' => $name],
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'color' => fake()->hexColor(),
            'is_featured' => fake()->boolean(50),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
