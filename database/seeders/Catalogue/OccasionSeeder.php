<?php

namespace Database\Seeders\Catalogue;

use App\Models\Catalogue\Occasion;
use Illuminate\Database\Seeder;

class OccasionSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['slug' => 'everyday', 'name' => ['uk' => 'Будь-який привід', 'en' => 'Everyday']],
            ['slug' => 'day', 'name' => ['uk' => 'День', 'en' => 'Day']],
            ['slug' => 'evening', 'name' => ['uk' => 'Вечір', 'en' => 'Evening']],
            ['slug' => 'office', 'name' => ['uk' => 'Офіс', 'en' => 'Office']],
            ['slug' => 'date', 'name' => ['uk' => 'Побачення', 'en' => 'Date']],
            ['slug' => 'business-meeting', 'name' => ['uk' => 'Ділова зустріч', 'en' => 'Business meeting']],
            ['slug' => 'party', 'name' => ['uk' => 'Вечірка', 'en' => 'Party']],
            ['slug' => 'gift', 'name' => ['uk' => 'Подарунок', 'en' => 'Gift']],
            ['slug' => 'sport', 'name' => ['uk' => 'Спорт', 'en' => 'Sport']],
        ];

        foreach ($rows as $i => $r) {
            Occasion::updateOrCreate(['slug' => $r['slug']], array_merge($r, ['sort_order' => $i, 'is_active' => true]));
        }
    }
}
