<?php

namespace Database\Seeders\Content;

use App\Enums\PageTemplate;
use App\Models\Content\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        // Static pages (About, Delivery, Payment, ...) are added manually
        // in the admin UI — no fixtures here.

        // The homepage placeholder: one block of each type so editors see
        // the page builder working on first boot. Slug is required by the
        // NOT NULL JSON column but is unused (routing finds the homepage
        // via is_homepage).
        Page::query()->updateOrCreate(
            ['is_homepage' => true],
            [
                'slug' => ['uk' => 'home-uk', 'en' => 'home-en'],
                'title' => ['uk' => 'Головна', 'en' => 'Home'],
                'intro' => ['uk' => '', 'en' => ''],
                'content' => null,
                'seo_title' => ['uk' => 'Головна', 'en' => 'Home'],
                'seo_description' => ['uk' => '', 'en' => ''],
                'is_published' => true,
                'template' => PageTemplate::Landing,
                'blocks' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'is_visible' => true,
                            'title' => ['uk' => 'Левант Парфюми', 'en' => 'Levant Parfums'],
                            'subtitle' => ['uk' => 'Аромати, що надихають', 'en' => 'Scents that inspire'],
                            'cta_label' => ['uk' => 'Каталог', 'en' => 'Shop'],
                            'cta_url' => '/products',
                        ],
                    ],
                    [
                        'type' => 'products',
                        'data' => [
                            'is_visible' => true,
                            'items' => [],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'data' => [
                            'is_visible' => true,
                            'body' => [
                                'uk' => 'Текст «про нас» — замініть у адмінці.',
                                'en' => 'About text — replace in the admin.',
                            ],
                        ],
                    ],
                    [
                        'type' => 'articles',
                        'data' => [
                            'is_visible' => true,
                            'items' => [],
                        ],
                    ],
                ],
            ],
        );
    }
}
