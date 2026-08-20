<?php

namespace Database\Seeders\Content;

use App\Models\Catalogue\Product;
use App\Models\Content\Article;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::query()->inRandomOrder()->limit(9)->get();
        if ($products->count() < 3) {
            return;
        }

        $chunks = $products->chunk(3)->values();

        foreach ($this->articles() as $index => $data) {
            $article = Article::query()
                ->whereJsonContains('slug->uk', $data['slug']['uk'])
                ->first();

            if ($article) {
                $article->fill($data)->save();
            } else {
                $article = Article::query()->create($data);
            }

            $article->products()->sync(
                $chunks[$index]
                    ->values()
                    ->mapWithKeys(fn (Product $product, int $position) => [
                        $product->id => ['sort_order' => $position],
                    ])
                    ->all(),
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function articles(): array
    {
        return [
            [
                'slug' => ['uk' => 'yak-obraty-svii-aromat', 'en' => 'how-to-choose-your-fragrance'],
                'title' => ['uk' => 'Як обрати свій аромат', 'en' => 'How to choose your fragrance'],
                'intro' => [
                    'uk' => 'Три прості кроки, які допоможуть знайти аромат для себе або для подарунка.',
                    'en' => 'Three simple steps to find a fragrance for yourself or as a gift.',
                ],
                'category' => ['uk' => 'Освіта', 'en' => 'Guides'],
                'read_time_minutes' => 4,
                'content' => [
                    'uk' => "## Почніть з настрою\n\nОберіть не ноту, а відчуття: свіжість для дня, тепло для вечора або виразний акцент для особливого випадку.\n\n## Дайте аромату час\n\nПерші ноти змінюються протягом 15–20 хвилин. Нанесіть аромат на шкіру і поверніться до нього трохи пізніше.\n\n## Приміряйте характер\n\nАромат має підтримувати ваш ритм і настрій. Довіряйте власному відчуттю, а не лише опису піраміди.",
                    'en' => "## Start with a mood\n\nChoose a feeling rather than a single note: freshness for daytime, warmth for evenings, or a bold accent for special moments.\n\n## Give it time\n\nTop notes change over 15–20 minutes. Apply the fragrance to skin and return to it later.\n\n## Wear the character\n\nA fragrance should support your rhythm and mood. Trust your own impression, not only the note pyramid.",
                ],
                'seo_title' => ['uk' => 'Як обрати аромат · Levant Parfums', 'en' => 'How to choose a fragrance · Levant Parfums'],
                'seo_description' => ['uk' => 'Практичний гід з вибору аромату для себе або на подарунок.', 'en' => 'A practical guide to choosing a fragrance for yourself or as a gift.'],
                'is_published' => true,
                'published_at' => now()->subDays(2),
            ],
            [
                'slug' => ['uk' => 'aromatyi-dlya-riznyh-sytuatsii', 'en' => 'fragrances-for-every-occasion'],
                'title' => ['uk' => 'Аромати для різних ситуацій', 'en' => 'Fragrances for every occasion'],
                'intro' => [
                    'uk' => 'Один гардероб ароматів може бути таким само виразним, як і гардероб одягу.',
                    'en' => 'A fragrance wardrobe can be as expressive as a wardrobe of clothes.',
                ],
                'category' => ['uk' => 'Колекції', 'en' => 'Collections'],
                'read_time_minutes' => 3,
                'content' => [
                    'uk' => "## Для робочого дня\n\nЧисті цитрусові, зелені та деревні композиції звучать зібрано й доречно поруч з іншими людьми.\n\n## Для вечора\n\nАмбра, спеції, ваніль і темні квіти створюють більш щільний, камерний шлейф.\n\n## Для себе\n\nНайкраща композиція — та, до якої хочеться повертатися незалежно від приводу.",
                    'en' => "## For a working day\n\nClean citrus, green and woody compositions feel composed and comfortable around other people.\n\n## For the evening\n\nAmber, spice, vanilla and dark florals create a denser, more intimate trail.\n\n## For yourself\n\nThe best composition is the one you want to return to, regardless of the occasion.",
                ],
                'seo_title' => ['uk' => 'Аромати для різних ситуацій · Levant Parfums', 'en' => 'Fragrances for every occasion · Levant Parfums'],
                'seo_description' => ['uk' => 'Як обрати аромат для робочого дня, вечора та особливих моментів.', 'en' => 'How to choose a fragrance for workdays, evenings and special moments.'],
                'is_published' => true,
                'published_at' => now()->subDay(),
            ],
            [
                'slug' => ['uk' => 'mystetstvo-parfumnogo-shleifu', 'en' => 'the-art-of-a-fragrance-trail'],
                'title' => ['uk' => 'Мистецтво парфумного шлейфу', 'en' => 'The art of a fragrance trail'],
                'intro' => [
                    'uk' => 'Стійкість і гучність — не одне й те саме. Розповідаємо, як носити аромат делікатно.',
                    'en' => 'Longevity and projection are not the same. Here is how to wear fragrance with ease.',
                ],
                'category' => ['uk' => 'Філософія', 'en' => 'Philosophy'],
                'read_time_minutes' => 3,
                'content' => [
                    'uk' => "## Менше — часто краще\n\nПочніть з одного-двох розпилень. На шкірі аромат розкривається м’якше, ніж у повітрі.\n\n## Точки нанесення\n\nТеплі ділянки — зап’ястя, шия, згин ліктя — допомагають композиції розвиватися поступово.\n\n## Ваш особистий простір\n\nГарний шлейф відчувається на близькій відстані, а не заповнює всю кімнату.",
                    'en' => "## Less is often more\n\nStart with one or two sprays. On skin, fragrance develops more softly than it does in the air.\n\n## Where to apply\n\nWarm points — wrists, neck and inner elbows — help a composition unfold gradually.\n\n## Your personal space\n\nA beautiful trail is noticed at close range, not across an entire room.",
                ],
                'seo_title' => ['uk' => 'Мистецтво парфумного шлейфу · Levant Parfums', 'en' => 'The art of a fragrance trail · Levant Parfums'],
                'seo_description' => ['uk' => 'Як наносити аромат, щоб він звучав делікатно та впевнено.', 'en' => 'How to apply fragrance so it feels elegant and confident.'],
                'is_published' => true,
                'published_at' => now(),
            ],
        ];
    }
}
