<?php

namespace Database\Seeders\Catalogue;

use App\Models\Catalogue\Concentration;
use Illuminate\Database\Seeder;

class ConcentrationSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['slug' => 'edp', 'abbreviation' => 'EDP', 'name' => ['uk' => 'Eau de Parfum', 'en' => 'Eau de Parfum']],
            ['slug' => 'edt', 'abbreviation' => 'EDT', 'name' => ['uk' => 'Eau de Toilette', 'en' => 'Eau de Toilette']],
            ['slug' => 'parfum', 'abbreviation' => 'PARF', 'name' => ['uk' => 'Parfum', 'en' => 'Parfum']],
            ['slug' => 'edc', 'abbreviation' => 'EDC', 'name' => ['uk' => 'Eau de Cologne', 'en' => 'Eau de Cologne']],
            ['slug' => 'extrait', 'abbreviation' => 'EXT', 'name' => ['uk' => 'Extrait', 'en' => 'Extrait']],
        ];

        foreach ($rows as $i => $r) {
            Concentration::updateOrCreate(['slug' => $r['slug']], array_merge($r, ['sort_order' => $i, 'is_active' => true]));
        }
    }
}
