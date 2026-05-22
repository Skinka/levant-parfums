<?php

namespace Database\Seeders\Catalogue;

use App\Models\Catalogue\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['slug' => 'tiziana-terenzi', 'name' => ['uk' => 'Tiziana Terenzi', 'en' => 'Tiziana Terenzi'], 'country' => 'IT'],
            ['slug' => 'maison-francis-kurkdjian', 'name' => ['uk' => 'Maison Francis Kurkdjian', 'en' => 'Maison Francis Kurkdjian'], 'country' => 'FR'],
            ['slug' => 'byredo', 'name' => ['uk' => 'Byredo', 'en' => 'Byredo'], 'country' => 'SE'],
            ['slug' => 'ex-nihilo', 'name' => ['uk' => 'Ex Nihilo', 'en' => 'Ex Nihilo'], 'country' => 'FR'],
            ['slug' => 'yves-saint-laurent', 'name' => ['uk' => 'Yves Saint Laurent', 'en' => 'Yves Saint Laurent'], 'country' => 'FR'],
            ['slug' => 'carolina-herrera', 'name' => ['uk' => 'Carolina Herrera', 'en' => 'Carolina Herrera'], 'country' => 'ES'],
            ['slug' => 'lancome', 'name' => ['uk' => 'Lancôme', 'en' => 'Lancôme'], 'country' => 'FR'],
            ['slug' => 'montale', 'name' => ['uk' => 'Montale', 'en' => 'Montale'], 'country' => 'FR'],
            ['slug' => 'van-cleef-arpels', 'name' => ['uk' => 'Van Cleef & Arpels', 'en' => 'Van Cleef & Arpels'], 'country' => 'FR'],
            ['slug' => 'amouage', 'name' => ['uk' => 'Amouage', 'en' => 'Amouage'], 'country' => 'OM'],
            ['slug' => 'kenzo', 'name' => ['uk' => 'Kenzo', 'en' => 'Kenzo'], 'country' => 'FR'],
            ['slug' => 'jo-malone-london', 'name' => ['uk' => 'Jo Malone London', 'en' => 'Jo Malone London'], 'country' => 'GB'],
            ['slug' => 'by-kilian', 'name' => ['uk' => 'By Kilian', 'en' => 'By Kilian'], 'country' => 'FR'],
            ['slug' => 'tom-ford', 'name' => ['uk' => 'Tom Ford', 'en' => 'Tom Ford'], 'country' => 'US'],
            ['slug' => 'creed', 'name' => ['uk' => 'Creed', 'en' => 'Creed'], 'country' => 'GB'],
            ['slug' => 'paco-rabanne', 'name' => ['uk' => 'Paco Rabanne', 'en' => 'Paco Rabanne'], 'country' => 'ES'],
            ['slug' => 'dior', 'name' => ['uk' => 'Dior', 'en' => 'Dior'], 'country' => 'FR'],
            ['slug' => 'marc-antoine-barrois', 'name' => ['uk' => 'Marc-Antoine Barrois', 'en' => 'Marc-Antoine Barrois'], 'country' => 'FR'],
        ];

        foreach ($rows as $i => $r) {
            Brand::updateOrCreate(['slug' => $r['slug']], array_merge($r, ['sort_order' => $i, 'is_active' => true]));
        }
    }
}
