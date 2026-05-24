<?php

namespace Database\Seeders\Catalogue;

use App\Models\Catalogue\Audience;
use App\Models\Catalogue\Brand;
use App\Models\Catalogue\Concentration;
use App\Models\Catalogue\Note;
use App\Models\Catalogue\Occasion;
use App\Models\Catalogue\PerfumeFamily;
use App\Models\Catalogue\Product;
use App\Models\Catalogue\Season;
use App\Models\Catalogue\Series;
use App\Models\Catalogue\Tag;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    private const IMAGE_DIR = __DIR__.'/../images';

    public function run(): void
    {
        $brands = Brand::pluck('id', 'slug');
        $families = PerfumeFamily::pluck('id', 'slug');
        $concentrations = Concentration::pluck('id', 'slug');
        $series = Series::pluck('id', 'slug');
        $notes = Note::pluck('id', 'slug');
        $tags = Tag::pluck('id', 'slug');
        $seasons = Season::pluck('id', 'slug');
        $occasions = Occasion::pluck('id', 'slug');
        $audiences = Audience::pluck('id', 'slug');

        $edpId = $concentrations['edp'];

        foreach ($this->products() as $i => $p) {
            $product = Product::updateOrCreate(
                ['sku' => $p['sku']],
                [
                    'slug' => $p['slug'],
                    'name' => $p['name'],
                    'tagline' => $p['tagline'],
                    'description' => $p['description'],
                    'character' => $p['character'] ?? null,
                    'why' => $p['why'] ?? null,
                    'sillage_score' => $p['sillage_score'] ?? null,
                    'longevity_hours' => $p['longevity_hours'] ?? null,
                    'inspired_perfume_name' => $p['inspired_perfume_name'],
                    'inspired_brand_id' => $brands[$p['inspired_brand_slug']] ?? null,
                    'volume_ml' => 50,
                    'gender' => $p['gender'],
                    'price_uah' => 1290.00,
                    'price_eur' => 35.00,
                    'in_stock' => true,
                    'is_published' => true,
                    'published_at' => now(),
                    'seo_title' => $p['name'],
                    'seo_description' => $p['tagline'],
                    'perfume_family_id' => $families[$p['family_slug']] ?? null,
                    'concentration_id' => $edpId,
                    'series_id' => $series[$p['series_slug']] ?? null,
                ],
            );

            $this->syncNotes($product, $p['notes_top'] ?? [], 'top', $notes);
            $this->syncNotes($product, $p['notes_heart'] ?? [], 'heart', $notes);
            $this->syncNotes($product, $p['notes_base'] ?? [], 'base', $notes);

            $product->tags()->sync($tags->only($p['tags'] ?? [])->values()->all());
            $product->seasons()->sync($seasons->only($p['seasons'] ?? [])->values()->all());
            $product->occasions()->sync($occasions->only($p['occasions'] ?? [])->values()->all());
            $product->audiences()->sync($audiences->only($p['audiences'] ?? [])->values()->all());

            $this->attachImages($product, $p['series_slug']);
        }
    }

    private function syncNotes(Product $product, array $slugs, string $level, $notes): void
    {
        $product->notes()->wherePivot('level', $level)->detach();
        foreach (array_values($slugs) as $sortOrder => $slug) {
            if (! isset($notes[$slug])) {
                continue;
            }
            $product->notes()->attach($notes[$slug], [
                'level' => $level,
                'sort_order' => $sortOrder,
            ]);
        }
    }

    private function attachImages(Product $product, string $seriesSlug): void
    {
        $primaryFile = $seriesSlug === 'onyx' ? 'onyx-box.jpg' : 'luxury-box.jpg';
        $primaryPath = self::IMAGE_DIR.'/'.$primaryFile;
        $gallery = [
            self::IMAGE_DIR.'/luxury-bottle.jpg',
            self::IMAGE_DIR.'/both-series.jpg',
        ];

        if ($product->getMedia('primary')->isEmpty() && file_exists($primaryPath)) {
            $product
                ->addMedia($primaryPath)
                ->preservingOriginal()
                ->toMediaCollection('primary');
        }

        if ($product->getMedia('gallery')->isEmpty()) {
            foreach ($gallery as $path) {
                if (file_exists($path)) {
                    $product
                        ->addMedia($path)
                        ->preservingOriginal()
                        ->toMediaCollection('gallery');
                }
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function products(): array
    {
        return [
            [
                'sku' => 'LV-LUX-01', 'slug' => 'luxury-1', 'series_slug' => 'luxury',
                'name' => ['uk' => 'LUXURY 1', 'en' => 'LUXURY 1'],
                'tagline' => ['uk' => 'Тропічний коктейль у флаконі', 'en' => 'Tropical cocktail in a bottle'],
                'description' => [
                    'uk' => 'Відкривається яскравим вибухом фруктів — маракуя, персик, малина. Делікатна конвалія в серці додає легкості. База з мускусу та сандалу залишає теплий шлейф на шкірі годинами.',
                    'en' => 'Opens with a bright burst of fruits — passion fruit, peach, raspberry. Delicate lily of the valley at the heart adds lightness. A musk and sandalwood base leaves a warm trail on the skin for hours.',
                ],
                'character' => ['uk' => 'Прохолодний шкіра, сухий цитрус і свіжа герань', 'en' => 'Cool skin, dry citrus and fresh geranium'],
                'why' => ['uk' => 'Універсальний денний підпис: легка серцева тиша й деревний слід без шуму.', 'en' => 'A universal daytime signature: a quiet heart and a calm woody trail.'],
                'sillage_score' => 3,
                'longevity_hours' => 6,
                'inspired_perfume_name' => 'Kirkè', 'inspired_brand_slug' => 'tiziana-terenzi',
                'family_slug' => 'floral', 'gender' => 'female',
                'notes_top' => ['maracuja', 'peach', 'raspberry', 'pear'],
                'notes_heart' => ['lily-of-the-valley'],
                'notes_base' => ['musk', 'sandalwood', 'vanilla', 'patchouli'],
                'tags' => ['bestseller'], 'seasons' => ['summer'],
                'occasions' => ['party', 'date'], 'audiences' => ['young', 'women'],
            ],
            [
                'sku' => 'LV-LUX-02', 'slug' => 'luxury-2', 'series_slug' => 'luxury',
                'name' => ['uk' => 'LUXURY 2', 'en' => 'LUXURY 2'],
                'tagline' => ['uk' => 'Алхімія кристала та вогню', 'en' => 'Alchemy of crystal and fire'],
                'description' => [
                    'uk' => 'Найвпізнаваніший нішевий аромат десятиліття. Шафран і жасмин відкриваються, амброва база залишається на шкірі годинами. Флагман LEVANT — аромат, який продає себе сам.',
                    'en' => 'The most recognisable niche fragrance of the decade. Saffron and jasmine open, an amber base lingers on skin for hours. LEVANT flagship — a scent that sells itself.',
                ],
                'character' => ['uk' => 'Тепла бавовна і кремовий мускус', 'en' => 'Warm cotton and creamy musk'],
                'why' => ['uk' => 'Для тих, хто шукає чистоту без пудри й гламуру.', 'en' => 'For those who want cleanliness without powder or gloss.'],
                'sillage_score' => 2,
                'longevity_hours' => 8,
                'inspired_perfume_name' => 'Baccarat Rouge 540', 'inspired_brand_slug' => 'maison-francis-kurkdjian',
                'family_slug' => 'oriental', 'gender' => 'unisex',
                'notes_top' => ['jasmine', 'saffron'],
                'notes_heart' => ['bitter-almond', 'cedar'],
                'notes_base' => ['amber', 'musk', 'woody-notes'],
                'tags' => ['bestseller'], 'seasons' => ['autumn', 'winter'],
                'occasions' => ['business-meeting', 'evening', 'gift'], 'audiences' => ['connoisseur'],
            ],
            [
                'sku' => 'LV-LUX-03', 'slug' => 'luxury-3', 'series_slug' => 'luxury',
                'name' => ['uk' => 'LUXURY 3', 'en' => 'LUXURY 3'],
                'tagline' => ['uk' => 'Африканський ранок у Парижі', 'en' => 'African morning in Paris'],
                'description' => [
                    'uk' => 'Свіжий і землистий водночас. Цитрусово-квіткове відкриття переходить у зелену серцевину й осідає теплим ветиверовим шлейфом. Ідеальний унісекс.',
                    'en' => 'Fresh and earthy at once. A citrus-floral opening glides into a green heart and settles into a warm vetiver trail. The perfect unisex.',
                ],
                'inspired_perfume_name' => "Bal d'Afrique", 'inspired_brand_slug' => 'byredo',
                'family_slug' => 'woody', 'gender' => 'unisex',
                'notes_top' => ['bergamot', 'lemon', 'marigold', 'neroli'],
                'notes_heart' => ['violet', 'cyclamen', 'jasmine'],
                'notes_base' => ['vetiver', 'cedar', 'amber', 'musk'],
                'tags' => ['new'], 'seasons' => ['spring', 'summer'],
                'occasions' => ['office', 'day'], 'audiences' => ['minimalist', 'connoisseur'],
            ],
            [
                'sku' => 'LV-LUX-04', 'slug' => 'luxury-4', 'series_slug' => 'luxury',
                'name' => ['uk' => 'LUXURY 4', 'en' => 'LUXURY 4'],
                'tagline' => ['uk' => 'Квітковий наркотик у краплі', 'en' => 'Floral narcotic in a drop'],
                'description' => [
                    'uk' => 'Бергамот і лічі відкриваються свіжістю. Серце півонії та жасмину — зачароване, паризьке, готове виходити. Повітряне, але невідступно притягальне.',
                    'en' => 'Bergamot and lychee open with freshness. A heart of peony and jasmine — enchanted, Parisian, ready to step out. Airy yet relentlessly magnetic.',
                ],
                'inspired_perfume_name' => 'Fleur Narcotique', 'inspired_brand_slug' => 'ex-nihilo',
                'family_slug' => 'floral', 'gender' => 'female',
                'notes_top' => ['lychee', 'bergamot', 'peach'],
                'notes_heart' => ['peony', 'orange', 'jasmine'],
                'notes_base' => ['musk', 'oakmoss', 'woody-notes'],
                'tags' => ['bestseller'], 'seasons' => ['spring'],
                'occasions' => ['date', 'day'], 'audiences' => ['young', 'women'],
            ],
            [
                'sku' => 'LV-LUX-05', 'slug' => 'luxury-5', 'series_slug' => 'luxury',
                'name' => ['uk' => 'LUXURY 5', 'en' => 'LUXURY 5'],
                'tagline' => ['uk' => 'Свобода у хвилі лаванди', 'en' => 'Freedom in a wave of lavender'],
                'description' => [
                    'uk' => 'Контраст лаванди з теплим ванільним серцем — і є Libre. Зухвалий, інтригуючий, не схожий ні на що, але впізнається одразу. Унісекс, який частіше носять жінки.',
                    'en' => 'The contrast of lavender with a warm vanilla heart — that is Libre. Bold, intriguing, unlike anything else, yet recognised instantly. A unisex more often worn by women.',
                ],
                'inspired_perfume_name' => 'Libre', 'inspired_brand_slug' => 'yves-saint-laurent',
                'family_slug' => 'fougere', 'gender' => 'unisex',
                'notes_top' => ['lavender', 'mandarin', 'orange'],
                'notes_heart' => ['white-musk', 'vanilla', 'jasmine'],
                'notes_base' => ['cedar', 'amber', 'patchouli'],
                'tags' => ['bestseller'], 'seasons' => ['spring', 'autumn'],
                'occasions' => ['everyday'], 'audiences' => ['character', 'connoisseur'],
            ],
            [
                'sku' => 'LV-LUX-06', 'slug' => 'luxury-6', 'series_slug' => 'luxury',
                'name' => ['uk' => 'LUXURY 6', 'en' => 'LUXURY 6'],
                'tagline' => ['uk' => 'Погана дівчинка. Ніжна душа.', 'en' => 'Bad girl. Tender soul.'],
                'description' => [
                    'uk' => 'Кавове відкриття з ноткою туберози та жасмину. Серце теплої тонки та шоколадний оксамит роблять цей аромат незабутнім. Бомба на будь-який вечір.',
                    'en' => 'A coffee opening with a hint of tuberose and jasmine. A heart of warm tonka and chocolate velvet makes this fragrance unforgettable. A bomb for any evening.',
                ],
                'inspired_perfume_name' => 'Good Girl', 'inspired_brand_slug' => 'carolina-herrera',
                'family_slug' => 'gourmand', 'gender' => 'female',
                'notes_top' => ['coffee', 'almond', 'tuberose'],
                'notes_heart' => ['jasmine', 'tonka-bean'],
                'notes_base' => ['sandalwood', 'cocoa', 'musk'],
                'tags' => ['bestseller'], 'seasons' => ['autumn', 'winter'],
                'occasions' => ['evening', 'gift'], 'audiences' => ['character', 'women'],
            ],
            [
                'sku' => 'LV-LUX-07', 'slug' => 'luxury-7', 'series_slug' => 'luxury',
                'name' => ['uk' => 'LUXURY 7', 'en' => 'LUXURY 7'],
                'tagline' => ['uk' => 'Краса життя, записана ароматом', 'en' => 'Beauty of life, written in scent'],
                'description' => [
                    'uk' => 'Ірис, чорна смородина та праліне. Цей аромат став знаковим у масовому сегменті завдяки теплому гурманному характеру. Гарантований комплімент.',
                    'en' => 'Iris, blackcurrant and praline. This fragrance became iconic in the mass segment thanks to its warm gourmand character. A guaranteed compliment.',
                ],
                'inspired_perfume_name' => 'La Vie Est Belle', 'inspired_brand_slug' => 'lancome',
                'family_slug' => 'gourmand', 'gender' => 'female',
                'notes_top' => ['iris', 'blackcurrant', 'pear'],
                'notes_heart' => ['jasmine', 'orange'],
                'notes_base' => ['praline', 'patchouli', 'vanilla', 'musk'],
                'tags' => ['bestseller'], 'seasons' => ['spring', 'summer', 'autumn', 'winter'],
                'occasions' => ['everyday', 'gift'], 'audiences' => ['mass', 'women'],
            ],
            [
                'sku' => 'LV-LUX-08', 'slug' => 'luxury-8', 'series_slug' => 'luxury',
                'name' => ['uk' => 'LUXURY 8', 'en' => 'LUXURY 8'],
                'tagline' => ['uk' => 'Свіжість зеленого прибою', 'en' => 'Freshness of a green surf'],
                'description' => [
                    'uk' => 'Бергамот, вербена і фенхель — ці три ноти створюють одну з найсвіжіших композицій MFK. База з матча додає глибини. Ідеальний для дня.',
                    'en' => 'Bergamot, verbena and fennel — these three notes form one of the freshest MFK compositions. A matcha base adds depth. Ideal for the day.',
                ],
                'inspired_perfume_name' => 'Aqua Media Cologne Forte', 'inspired_brand_slug' => 'maison-francis-kurkdjian',
                'family_slug' => 'aquatic', 'gender' => 'unisex',
                'notes_top' => ['verbena', 'bergamot'],
                'notes_heart' => ['fennel', 'green-freshness'],
                'notes_base' => ['matcha', 'musk', 'woody-notes'],
                'tags' => ['new'], 'seasons' => ['summer'],
                'occasions' => ['office', 'day'], 'audiences' => ['connoisseur'],
            ],
            [
                'sku' => 'LV-LUX-09', 'slug' => 'luxury-9', 'series_slug' => 'luxury',
                'name' => ['uk' => 'LUXURY 9', 'en' => 'LUXURY 9'],
                'tagline' => ['uk' => 'Спеція Шовкового шляху', 'en' => 'Spice of the Silk Road'],
                'description' => [
                    'uk' => 'Шафран розпалює вогонь. Серце уду та троянди дає східну розкіш і драматизм. Тонка боб у базі робить аромат огортаючим і стійким.',
                    'en' => 'Saffron ignites the fire. A heart of oud and rose lends oriental luxury and drama. Tonka bean at the base makes the scent enveloping and lasting.',
                ],
                'inspired_perfume_name' => 'Arabians Tonka', 'inspired_brand_slug' => 'montale',
                'family_slug' => 'oriental', 'gender' => 'unisex',
                'notes_top' => ['saffron', 'bergamot'],
                'notes_heart' => ['oud', 'rose'],
                'notes_base' => ['tonka-bean', 'amber', 'musk'],
                'tags' => ['limited'], 'seasons' => ['autumn', 'winter'],
                'occasions' => ['evening'], 'audiences' => ['connoisseur'],
            ],
            [
                'sku' => 'LV-LUX-10', 'slug' => 'luxury-10', 'series_slug' => 'luxury',
                'name' => ['uk' => 'LUXURY 10', 'en' => 'LUXURY 10'],
                'tagline' => ['uk' => 'Пачулі — переродженi', 'en' => 'Patchouli — reborn'],
                'description' => [
                    'uk' => 'Знайома нота у новому звучанні. Рожевий перець і троянда пом’якшують пачулі. Чистий фінал на білому мускусі. Унісекс для всіх сезонів.',
                    'en' => 'A familiar note in a new key. Pink pepper and rose soften the patchouli. A clean finish on white musk. Unisex for all seasons.',
                ],
                'inspired_perfume_name' => 'Patchouli Blanc', 'inspired_brand_slug' => 'van-cleef-arpels',
                'family_slug' => 'chypre', 'gender' => 'unisex',
                'notes_top' => ['aldehydes', 'pink-pepper'],
                'notes_heart' => ['patchouli', 'rose'],
                'notes_base' => ['white-musk', 'cashmere-wood'],
                'tags' => [], 'seasons' => ['spring', 'summer', 'autumn', 'winter'],
                'occasions' => ['everyday', 'office'], 'audiences' => ['minimalist'],
            ],
            [
                'sku' => 'LV-LUX-11', 'slug' => 'luxury-11', 'series_slug' => 'luxury',
                'name' => ['uk' => 'LUXURY 11', 'en' => 'LUXURY 11'],
                'tagline' => ['uk' => 'Синій талісман щастя', 'en' => 'Blue talisman of happiness'],
                'description' => [
                    'uk' => 'Груша та цитрус відкриваються легко й гармонійно. Унікальна космічна свіжість осідає на шкірі стійким мускусом. Один з найкомпліментарніших ароматів серед знавців.',
                    'en' => 'Pear and citrus open lightly and harmoniously. A unique cosmic freshness settles on the skin as enduring musk. One of the most complimented fragrances among connoisseurs.',
                ],
                'inspired_perfume_name' => 'Blue Talisman', 'inspired_brand_slug' => 'ex-nihilo',
                'family_slug' => 'aquatic', 'gender' => 'unisex',
                'notes_top' => ['pear', 'bergamot', 'mandarin', 'ginger'],
                'notes_heart' => ['orange', 'musk'],
                'notes_base' => ['musk', 'ambrofix', 'cedar'],
                'tags' => ['bestseller'], 'seasons' => ['spring', 'summer'],
                'occasions' => ['everyday'], 'audiences' => ['connoisseur'],
            ],
            [
                'sku' => 'LV-LUX-12', 'slug' => 'luxury-12', 'series_slug' => 'luxury',
                'name' => ['uk' => 'LUXURY 12', 'en' => 'LUXURY 12'],
                'tagline' => ['uk' => 'Провідник крізь сад відчуттів', 'en' => 'A guide through a garden of sensations'],
                'description' => [
                    'uk' => 'Груша та мигдаль — повітряне відкриття. Серце троянди, османтуса й шафрану — це Amouage на піку своїх можливостей. Безкомпромісна розкіш і стійкість.',
                    'en' => 'Pear and almond — an airy opening. A heart of rose, osmanthus and saffron — this is Amouage at its peak. Uncompromising luxury and longevity.',
                ],
                'inspired_perfume_name' => 'Guidance', 'inspired_brand_slug' => 'amouage',
                'family_slug' => 'oriental', 'gender' => 'unisex',
                'notes_top' => ['pear', 'hazelnut', 'frankincense', 'fig'],
                'notes_heart' => ['osmanthus', 'rose', 'saffron', 'jasmine'],
                'notes_base' => ['sandalwood', 'vanilla', 'amber', 'labdanum'],
                'tags' => ['limited'], 'seasons' => ['autumn', 'winter'],
                'occasions' => ['evening', 'gift'], 'audiences' => ['connoisseur'],
            ],
            [
                'sku' => 'LV-LUX-13', 'slug' => 'luxury-13', 'series_slug' => 'luxury',
                'name' => ['uk' => 'LUXURY 13', 'en' => 'LUXURY 13'],
                'tagline' => ['uk' => 'Деревна свіжість міського ранку', 'en' => 'Woody freshness of a city morning'],
                'description' => [
                    'uk' => 'Ароматичне деревне серце Kenzo Homme з цитрусовим відкриттям. Тютюново-деревна серцевина з базою ветиверу. Надійний, ненав’язливий, всесезонний.',
                    'en' => 'The aromatic woody heart of Kenzo Homme with a citrus opening. A tobacco-woody core with a vetiver base. Reliable, unobtrusive, all-season.',
                ],
                'inspired_perfume_name' => 'Kenzo Homme', 'inspired_brand_slug' => 'kenzo',
                'family_slug' => 'woody', 'gender' => 'unisex',
                'notes_top' => ['bergamot', 'grapefruit', 'neroli'],
                'notes_heart' => ['tobacco', 'cedar', 'lavender'],
                'notes_base' => ['vetiver', 'oakmoss', 'tonka-bean'],
                'tags' => [], 'seasons' => ['spring', 'summer', 'autumn', 'winter'],
                'occasions' => ['everyday', 'office', 'gift'], 'audiences' => ['mass'],
            ],
            [
                'sku' => 'LV-LUX-14', 'slug' => 'luxury-14', 'series_slug' => 'luxury',
                'name' => ['uk' => 'LUXURY 14', 'en' => 'LUXURY 14'],
                'tagline' => ['uk' => 'Флердоранж на щоці', 'en' => 'Orange blossom on the cheek'],
                'description' => [
                    'uk' => 'Легке, яскраве, справжнє. Клементин відкриває, апельсин підсилює. Ніжна мускусно-деревна база залишає слід протягом дня.',
                    'en' => 'Light, bright, real. Clementine opens, orange amplifies. A tender musky-woody base leaves a trace throughout the day.',
                ],
                'inspired_perfume_name' => 'Orange Blossom', 'inspired_brand_slug' => 'jo-malone-london',
                'family_slug' => 'citrus', 'gender' => 'unisex',
                'notes_top' => ['clementine', 'orange', 'hyacinth'],
                'notes_heart' => ['orange'],
                'notes_base' => ['musk', 'woody-notes'],
                'tags' => ['new'], 'seasons' => ['spring', 'summer'],
                'occasions' => ['day', 'everyday'], 'audiences' => ['mass'],
            ],
            [
                'sku' => 'LV-LUX-15', 'slug' => 'luxury-15', 'series_slug' => 'luxury',
                'name' => ['uk' => 'LUXURY 15', 'en' => 'LUXURY 15'],
                'tagline' => ['uk' => 'Частка ангелів — у твоєму келиху', 'en' => "Angels' share — in your glass"],
                'description' => [
                    'uk' => 'Натхнення коньяку та кориця розпалюють. Серце праліне з горіховим відтінком. Ванільно-карамельна база готова залишатися вечір за вечором.',
                    'en' => 'Cognac inspiration and cinnamon ignite. A praline heart with nutty nuance. A vanilla-caramel base ready to stay evening after evening.',
                ],
                'inspired_perfume_name' => "Angels' Share", 'inspired_brand_slug' => 'by-kilian',
                'family_slug' => 'gourmand', 'gender' => 'unisex',
                'notes_top' => ['cognac', 'rum', 'cinnamon'],
                'notes_heart' => ['praline', 'nut-tree'],
                'notes_base' => ['vanilla', 'tonka-bean', 'caramel'],
                'tags' => ['bestseller'], 'seasons' => ['autumn', 'winter'],
                'occasions' => ['evening', 'gift'], 'audiences' => ['connoisseur'],
            ],
            [
                'sku' => 'LV-LUX-16', 'slug' => 'luxury-16', 'series_slug' => 'luxury',
                'name' => ['uk' => 'LUXURY 16', 'en' => 'LUXURY 16'],
                'tagline' => ['uk' => 'Старомодний. У кращому сенсі.', 'en' => 'Old fashioned. In the best sense.'],
                'description' => [
                    'uk' => 'Натхненний віскі 18-річної витримки. Пшениця та давана відкриваються, кедр та іммортель у серці. Тепла смолиста бочка та довгі вечірні шлейфи.',
                    'en' => 'Inspired by 18-year-aged whisky. Wheat and davana open, cedar and immortelle in the heart. A warm resinous barrel and long evening trails.',
                ],
                'inspired_perfume_name' => 'Old Fashioned', 'inspired_brand_slug' => 'by-kilian',
                'family_slug' => 'woody', 'gender' => 'unisex',
                'notes_top' => ['wheat', 'davana'],
                'notes_heart' => ['cedar', 'immortelle'],
                'notes_base' => ['styrax', 'tolu-balsam', 'oak'],
                'tags' => ['limited'], 'seasons' => ['autumn', 'winter'],
                'occasions' => ['evening', 'gift'], 'audiences' => ['connoisseur'],
            ],
            [
                'sku' => 'LV-LUX-17', 'slug' => 'luxury-17', 'series_slug' => 'luxury',
                'name' => ['uk' => 'LUXURY 17', 'en' => 'LUXURY 17'],
                'tagline' => ['uk' => 'Втрачена вишня', 'en' => 'Lost cherry'],
                'description' => [
                    'uk' => 'Солодко-пряна вишня відкривається з гірким мигдалем. Троянда та жасмин додають глибини. Тепла смолиста база з сандалом — чуттєва нитка крізь сезони.',
                    'en' => 'Sweet-spicy cherry opens with bitter almond. Rose and jasmine add depth. A warm resinous base with sandalwood — a sensual thread across seasons.',
                ],
                'inspired_perfume_name' => 'Lost Cherry', 'inspired_brand_slug' => 'tom-ford',
                'family_slug' => 'oriental', 'gender' => 'unisex',
                'notes_top' => ['black-cherry', 'bitter-almond'],
                'notes_heart' => ['turkish-rose', 'jasmine'],
                'notes_base' => ['sandalwood', 'benzoin', 'peruvian-balsam'],
                'tags' => ['bestseller', 'limited'], 'seasons' => ['autumn', 'winter'],
                'occasions' => ['evening', 'gift'], 'audiences' => ['connoisseur'],
            ],
            [
                'sku' => 'LV-ONX-01', 'slug' => 'onyx-1', 'series_slug' => 'onyx',
                'name' => ['uk' => 'ONYX 1', 'en' => 'ONYX 1'],
                'tagline' => ['uk' => 'Перемога з першого подиху', 'en' => 'Victory from the first breath'],
                'description' => [
                    'uk' => 'Культовий аромат для тих, хто знає смак. Ананас і береза — контраст, що став легендою. Маскулінний, амбітний, незабутній.',
                    'en' => 'An iconic fragrance for those who know. Pineapple and birch — a contrast that became legend. Masculine, ambitious, unforgettable.',
                ],
                'character' => ['uk' => 'Чорний шкіра, темний удд і копчений ладан', 'en' => 'Black leather, dark oud and smoked frankincense'],
                'why' => ['uk' => 'Вечірня самість, що не намагається сподобатись. Шлейф — як підпис у темряві.', 'en' => 'An evening self that does not try to please. A trail like a signature in the dark.'],
                'sillage_score' => 5,
                'longevity_hours' => 10,
                'inspired_perfume_name' => 'Aventus', 'inspired_brand_slug' => 'creed',
                'family_slug' => 'chypre', 'gender' => 'male',
                'notes_top' => ['pineapple', 'bergamot', 'blackcurrant'],
                'notes_heart' => ['birch', 'jasmine', 'patchouli'],
                'notes_base' => ['oakmoss', 'amber', 'musk'],
                'tags' => ['bestseller'], 'seasons' => ['spring', 'summer', 'autumn', 'winter'],
                'occasions' => ['business-meeting', 'everyday'], 'audiences' => ['men', 'connoisseur'],
            ],
            [
                'sku' => 'LV-ONX-02', 'slug' => 'onyx-2', 'series_slug' => 'onyx',
                'name' => ['uk' => 'ONYX 2', 'en' => 'ONYX 2'],
                'tagline' => ['uk' => 'Нездоланний. Не на словах.', 'en' => 'Invincible. Not just words.'],
                'description' => [
                    'uk' => 'Морська свіжість грейпфрута поєднується з підводними деревними нотами бази. Класичний чоловічий аромат для будь-якого приводу. Широка аудиторія.',
                    'en' => 'Marine freshness of grapefruit meets underwater woody notes at the base. A classic men’s fragrance for any occasion. Broad appeal.',
                ],
                'character' => ['uk' => 'Гіркий шоколад, тютюн і кориця', 'en' => 'Bitter chocolate, tobacco and cinnamon'],
                'why' => ['uk' => 'Для холодних вечорів, коли треба тримати тепло близько.', 'en' => 'For cold evenings when warmth must stay close.'],
                'sillage_score' => 4,
                'longevity_hours' => 12,
                'inspired_perfume_name' => 'Invictus', 'inspired_brand_slug' => 'paco-rabanne',
                'family_slug' => 'aquatic', 'gender' => 'male',
                'notes_top' => ['grapefruit', 'marine-accord', 'mandarin'],
                'notes_heart' => ['jasmine', 'bay-leaves'],
                'notes_base' => ['guaiac', 'amber', 'oakmoss'],
                'tags' => ['bestseller'], 'seasons' => ['spring', 'summer'],
                'occasions' => ['day', 'sport', 'everyday'], 'audiences' => ['young', 'men'],
            ],
            [
                'sku' => 'LV-ONX-03', 'slug' => 'onyx-3', 'series_slug' => 'onyx',
                'name' => ['uk' => 'ONYX 3', 'en' => 'ONYX 3'],
                'tagline' => ['uk' => 'Дикий. Але не безконтрольний.', 'en' => 'Wild. But not uncontrolled.'],
                'description' => [
                    'uk' => 'Калабрійський бергамот і перець — зухвале відкриття. Амброксанова база тримає міцно й довго. Один з найпопулярніших чоловічих ароматів у світі.',
                    'en' => 'Calabrian bergamot and pepper — a bold opening. An ambroxan base holds strong and long. One of the most popular men’s fragrances in the world.',
                ],
                'inspired_perfume_name' => 'Sauvage', 'inspired_brand_slug' => 'dior',
                'family_slug' => 'woody', 'gender' => 'male',
                'notes_top' => ['bergamot', 'pepper', 'boxwood'],
                'notes_heart' => ['lavender', 'geranium', 'violet'],
                'notes_base' => ['ambroxan', 'cedar', 'vetiver'],
                'tags' => ['bestseller'], 'seasons' => ['spring', 'summer', 'autumn', 'winter'],
                'occasions' => ['everyday', 'gift'], 'audiences' => ['men', 'mass'],
            ],
            [
                'sku' => 'LV-ONX-04', 'slug' => 'onyx-4', 'series_slug' => 'onyx',
                'name' => ['uk' => 'ONYX 4', 'en' => 'ONYX 4'],
                'tagline' => ['uk' => 'Мінеральна краса далекої планети', 'en' => 'Mineral beauty of a distant planet'],
                'description' => [
                    'uk' => 'Шафран і мандарин відкривають космічність. Мінеральна замша з ноткою солоного вітру робить цей аромат унікальним. Привертає увагу, стає темою розмови.',
                    'en' => 'Saffron and mandarin open with a cosmic vibe. Mineral suede with a salty-wind note makes this scent unique. Draws attention and sparks conversation.',
                ],
                'inspired_perfume_name' => 'Ganymede', 'inspired_brand_slug' => 'marc-antoine-barrois',
                'family_slug' => 'woody', 'gender' => 'male',
                'notes_top' => ['saffron', 'mandarin'],
                'notes_heart' => ['osmanthus', 'immortelle', 'violet'],
                'notes_base' => ['mineral', 'suede', 'musk', 'cedar'],
                'tags' => ['limited'], 'seasons' => ['autumn', 'winter'],
                'occasions' => ['everyday', 'evening'], 'audiences' => ['connoisseur', 'men'],
            ],
            [
                'sku' => 'LV-ONX-05', 'slug' => 'onyx-5', 'series_slug' => 'onyx',
                'name' => ['uk' => 'ONYX 5', 'en' => 'ONYX 5'],
                'tagline' => ['uk' => 'Темрява, що залишає підпис', 'en' => 'Darkness that leaves a signature'],
                'description' => [
                    'uk' => 'Глибокий, пряний, орієнтальний. Шафран і тютюн створюють напружену драматичність. База з амбри та мускусу — шлейф, що чутно здалека.',
                    'en' => 'Deep, spicy, oriental. Saffron and tobacco create tense drama. An amber-musk base — a trail audible from afar.',
                ],
                'inspired_perfume_name' => 'Megamaera', 'inspired_brand_slug' => 'tiziana-terenzi',
                'family_slug' => 'oriental', 'gender' => 'male',
                'notes_top' => ['black-pepper', 'saffron', 'bergamot'],
                'notes_heart' => ['tobacco', 'oud', 'patchouli'],
                'notes_base' => ['amber', 'musk', 'resin', 'labdanum'],
                'tags' => ['limited'], 'seasons' => ['autumn', 'winter'],
                'occasions' => ['evening'], 'audiences' => ['connoisseur', 'men'],
            ],
        ];
    }
}
