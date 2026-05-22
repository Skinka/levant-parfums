<?php

namespace Database\Seeders\Catalogue;

use App\Models\Catalogue\Audience;
use Illuminate\Database\Seeder;

class AudienceSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['slug' => 'young', 'name' => ['uk' => 'Молода аудиторія', 'en' => 'Young audience']],
            ['slug' => 'connoisseur', 'name' => ['uk' => 'Цінителі', 'en' => 'Connoisseurs']],
            ['slug' => 'minimalist', 'name' => ['uk' => 'Мінімалістичний клієнт', 'en' => 'Minimalist']],
            ['slug' => 'character', 'name' => ['uk' => 'З характером', 'en' => 'Bold character']],
            ['slug' => 'mass', 'name' => ['uk' => 'Широка аудиторія', 'en' => 'Mass audience']],
            ['slug' => 'women', 'name' => ['uk' => 'Жінки', 'en' => 'Women']],
            ['slug' => 'men', 'name' => ['uk' => 'Чоловіки', 'en' => 'Men']],
        ];

        foreach ($rows as $i => $r) {
            Audience::updateOrCreate(['slug' => $r['slug']], array_merge($r, ['sort_order' => $i, 'is_active' => true]));
        }
    }
}
