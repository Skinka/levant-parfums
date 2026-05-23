<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Catalogue\AudienceSeeder;
use Database\Seeders\Catalogue\BrandSeeder;
use Database\Seeders\Catalogue\ConcentrationSeeder;
use Database\Seeders\Catalogue\NoteSeeder;
use Database\Seeders\Catalogue\OccasionSeeder;
use Database\Seeders\Catalogue\PerfumeFamilySeeder;
use Database\Seeders\Catalogue\ProductSeeder;
use Database\Seeders\Catalogue\SeasonSeeder;
use Database\Seeders\Catalogue\SeriesSeeder;
use Database\Seeders\Catalogue\TagSeeder;
use Database\Seeders\Content\ArticleSeeder;
use Database\Seeders\Content\PageSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@levantparfums.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $this->call([
            PerfumeFamilySeeder::class,
            ConcentrationSeeder::class,
            SeasonSeeder::class,
            TagSeeder::class,
            SeriesSeeder::class,
            OccasionSeeder::class,
            AudienceSeeder::class,
            BrandSeeder::class,
            NoteSeeder::class,
            ProductSeeder::class,
            PageSeeder::class,
            ArticleSeeder::class,
        ]);
    }
}
