<?php

namespace Database\Seeders\Catalogue;

use App\Models\Catalogue\Season;
use Illuminate\Database\Seeder;

class SeasonSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['slug' => 'spring', 'name' => ['uk' => 'Весна', 'en' => 'Spring']],
            ['slug' => 'summer', 'name' => ['uk' => 'Літо', 'en' => 'Summer']],
            ['slug' => 'autumn', 'name' => ['uk' => 'Осінь', 'en' => 'Autumn']],
            ['slug' => 'winter', 'name' => ['uk' => 'Зима', 'en' => 'Winter']],
        ];

        foreach ($rows as $i => $r) {
            Season::updateOrCreate(['slug' => $r['slug']], array_merge($r, ['sort_order' => $i, 'is_active' => true]));
        }
    }
}
