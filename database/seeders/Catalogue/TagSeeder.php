<?php

namespace Database\Seeders\Catalogue;

use App\Models\Catalogue\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['slug' => 'bestseller', 'color' => '#C77B7B', 'name' => ['uk' => 'Бестселер', 'en' => 'Bestseller']],
            ['slug' => 'new', 'color' => '#7CB87A', 'name' => ['uk' => 'Новинка', 'en' => 'New']],
            ['slug' => 'sale', 'color' => '#D4A04C', 'name' => ['uk' => 'Акція', 'en' => 'Sale']],
            ['slug' => 'limited', 'color' => '#8B6F8B', 'name' => ['uk' => 'Лімітка', 'en' => 'Limited']],
        ];

        foreach ($rows as $i => $r) {
            Tag::updateOrCreate(['slug' => $r['slug']], array_merge($r, ['sort_order' => $i, 'is_active' => true, 'is_featured' => true]));
        }
    }
}
