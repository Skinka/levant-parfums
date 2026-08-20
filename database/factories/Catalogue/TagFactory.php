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
        $name = $this->faker->unique()->word();

        return [
            'name' => ['uk' => $name, 'en' => $name],
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(1, 99999),
            'color' => $this->faker->hexColor(),
            'is_featured' => $this->faker->boolean(50),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
