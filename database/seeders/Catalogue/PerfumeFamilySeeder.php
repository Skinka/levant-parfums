<?php

namespace Database\Seeders\Catalogue;

use App\Models\Catalogue\PerfumeFamily;
use Illuminate\Database\Seeder;

class PerfumeFamilySeeder extends Seeder
{
    public function run(): void
    {
        $families = [
            ['slug' => 'citrus', 'name' => ['uk' => 'Цитрусове', 'en' => 'Citrus']],
            ['slug' => 'floral', 'name' => ['uk' => 'Квіткове', 'en' => 'Floral']],
            ['slug' => 'fougere', 'name' => ['uk' => 'Фужерне', 'en' => 'Fougère']],
            ['slug' => 'woody', 'name' => ['uk' => 'Деревне', 'en' => 'Woody']],
            ['slug' => 'oriental', 'name' => ['uk' => 'Східне', 'en' => 'Oriental']],
            ['slug' => 'chypre', 'name' => ['uk' => 'Шипрове', 'en' => 'Chypre']],
            ['slug' => 'gourmand', 'name' => ['uk' => 'Гурманське', 'en' => 'Gourmand']],
            ['slug' => 'aquatic', 'name' => ['uk' => 'Акватичне', 'en' => 'Aquatic']],
        ];

        foreach ($families as $i => $f) {
            PerfumeFamily::updateOrCreate(
                ['slug' => $f['slug']],
                array_merge($f, ['sort_order' => $i, 'is_active' => true]),
            );
        }
    }
}
