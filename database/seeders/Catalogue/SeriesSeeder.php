<?php

namespace Database\Seeders\Catalogue;

use App\Models\Catalogue\Series;
use Illuminate\Database\Seeder;

class SeriesSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['slug' => 'luxury', 'name' => ['uk' => 'Luxury', 'en' => 'Luxury'], 'theme_class' => 'theme-cream'],
            ['slug' => 'onyx', 'name' => ['uk' => 'Onyx', 'en' => 'Onyx'], 'theme_class' => 'theme-onyx'],
        ];

        foreach ($rows as $i => $r) {
            Series::updateOrCreate(['slug' => $r['slug']], array_merge($r, ['sort_order' => $i, 'is_active' => true]));
        }
    }
}
