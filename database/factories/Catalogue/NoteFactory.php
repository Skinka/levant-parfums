<?php

namespace Database\Factories\Catalogue;

use App\Models\Catalogue\Note;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ['uk' => $name, 'en' => $name],
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'description' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
