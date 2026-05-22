<?php

use App\Models\Catalogue\Concentration;
use App\Models\Catalogue\PerfumeFamily;
use App\Models\Catalogue\Season;
use App\Models\Catalogue\Tag;
use Database\Seeders\DatabaseSeeder;

it('seeds all catalogue dictionaries idempotently', function () {
    $this->seed(DatabaseSeeder::class);

    expect(PerfumeFamily::count())->toBe(8);
    expect(Concentration::count())->toBe(5);
    expect(Season::count())->toBe(4);
    expect(Tag::count())->toBe(4);

    // Re-run: counts must remain stable (idempotency via updateOrCreate)
    $this->seed(DatabaseSeeder::class);

    expect(PerfumeFamily::count())->toBe(8);
    expect(Tag::count())->toBe(4);
});
