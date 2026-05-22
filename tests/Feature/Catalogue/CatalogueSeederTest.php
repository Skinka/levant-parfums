<?php

use App\Enums\NoteLevel;
use App\Models\Catalogue\Brand;
use App\Models\Catalogue\Concentration;
use App\Models\Catalogue\Note;
use App\Models\Catalogue\PerfumeFamily;
use App\Models\Catalogue\Product;
use App\Models\Catalogue\Season;
use App\Models\Catalogue\Series;
use App\Models\Catalogue\Tag;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('seeds all catalogue dictionaries idempotently', function () {
    $this->seed(DatabaseSeeder::class);

    expect(PerfumeFamily::count())->toBe(8);
    expect(Concentration::count())->toBe(5);
    expect(Season::count())->toBe(4);
    expect(Tag::count())->toBe(4);
    expect(Series::count())->toBe(2);
    expect(Brand::count())->toBe(18);
    expect(Note::count())->toBe(85);

    // Re-run: counts must remain stable (idempotency via updateOrCreate)
    $this->seed(DatabaseSeeder::class);

    expect(PerfumeFamily::count())->toBe(8);
    expect(Tag::count())->toBe(4);
    expect(Brand::count())->toBe(18);
});

it('seeds 22 products from the LEVANT presentation', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Product::count())->toBe(22);
    expect(Product::where('series_id', Series::where('slug', 'luxury')->value('id'))->count())->toBe(17);
    expect(Product::where('series_id', Series::where('slug', 'onyx')->value('id'))->count())->toBe(5);

    $luxury2 = Product::where('slug', 'luxury-2')->first();
    expect($luxury2->inspired_perfume_name)->toBe('Baccarat Rouge 540');
    expect($luxury2->inspiredBrand->slug)->toBe('maison-francis-kurkdjian');
    expect($luxury2->notesByLevel(NoteLevel::Top)->pluck('slug')->all())->toEqualCanonicalizing(['jasmine', 'saffron']);

    $onyx3 = Product::where('slug', 'onyx-3')->first();
    expect($onyx3->inspired_perfume_name)->toBe('Sauvage');
    expect($onyx3->gender->value)->toBe('male');
    expect((string) $onyx3->price_uah)->toBe('1290.00');
});

it('attaches primary and gallery media to each seeded product', function () {
    $this->seed(DatabaseSeeder::class);

    Product::all()->each(function (Product $p) {
        expect($p->getMedia('primary'))->toHaveCount(1);
        expect($p->getMedia('gallery'))->toHaveCount(2);
    });
});
