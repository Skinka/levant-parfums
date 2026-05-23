<?php

namespace Database\Factories\Content;

use App\Models\Content\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $titleUk = 'Стаття '.fake()->unique()->numberBetween(1, 99999);
        $titleEn = 'Article '.fake()->unique()->numberBetween(1, 99999);

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
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['is_published' => false, 'published_at' => null]);
    }

    public function scheduled(\DateTimeInterface $at): static
    {
        return $this->state(['is_published' => true, 'published_at' => $at]);
    }
}
