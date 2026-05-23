<?php

namespace Database\Factories\Content;

use App\Models\Content\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $titleUk = 'Сторінка '.fake()->unique()->numberBetween(1, 99999);
        $titleEn = 'Page '.fake()->unique()->numberBetween(1, 99999);

        return [
            'slug' => [
                'uk' => Str::slug($titleUk).'-'.Str::random(4),
                'en' => Str::slug($titleEn).'-'.Str::random(4),
            ],
            'title' => ['uk' => $titleUk, 'en' => $titleEn],
            'intro' => ['uk' => fake('uk_UA')->sentence(), 'en' => fake()->sentence()],
            'content' => ['uk' => fake('uk_UA')->paragraphs(2, true), 'en' => fake()->paragraphs(2, true)],
            'seo_title' => ['uk' => $titleUk, 'en' => $titleEn],
            'seo_description' => ['uk' => fake('uk_UA')->sentence(), 'en' => fake()->sentence()],
            'is_published' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(['is_published' => false]);
    }
}
