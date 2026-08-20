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
        $titleUk = 'Стаття '.$this->faker->unique()->numberBetween(1, 99999);
        $titleEn = 'Article '.$this->faker->unique()->numberBetween(1, 99999);

        return [
            'slug' => [
                'uk' => Str::slug($titleUk).'-'.Str::random(4),
                'en' => Str::slug($titleEn).'-'.Str::random(4),
            ],
            'title' => ['uk' => $titleUk, 'en' => $titleEn],
            'intro' => ['uk' => $this->faker->sentence(), 'en' => $this->faker->sentence()],
            'category' => [
                'uk' => $this->faker->randomElement(['Філософія', 'Маніфест', 'Освіта', 'Колекції']),
                'en' => $this->faker->randomElement(['Philosophy', 'Manifesto', 'Education', 'Collections']),
            ],
            'read_time_minutes' => $this->faker->numberBetween(3, 8),
            'content' => ['uk' => $this->faker->paragraphs(2, true), 'en' => $this->faker->paragraphs(2, true)],
            'seo_title' => ['uk' => $titleUk, 'en' => $titleEn],
            'seo_description' => ['uk' => $this->faker->sentence(), 'en' => $this->faker->sentence()],
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
