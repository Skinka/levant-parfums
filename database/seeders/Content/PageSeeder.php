<?php

namespace Database\Seeders\Content;

use App\Enums\PageTemplate;
use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;
use App\Models\Content\Article;
use App\Models\Content\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $this->copyAsset('levant-luxury-bottle.jpg');
        $this->copyAsset('levant-flacon-3.jpg');
        $this->copyAsset('levant-flacon-4.jpg');

        $luxury = Series::where('slug', 'luxury')->first();
        $onyx = Series::where('slug', 'onyx')->first();

        $bestsellers = Product::query()
            ->whereHas('tags', fn ($q) => $q->where('slug', 'bestseller'))
            ->limit(6)
            ->pluck('id')
            ->all();
        if (empty($bestsellers)) {
            $bestsellers = Product::query()->limit(6)->pluck('id')->all();
        }

        $newItems = Product::query()
            ->whereHas('tags', fn ($q) => $q->where('slug', 'new'))
            ->limit(6)
            ->pluck('id')
            ->all();
        if (empty($newItems)) {
            $newItems = Product::query()->orderByDesc('id')->limit(6)->pluck('id')->all();
        }

        $articleIds = Article::query()
            ->orderByDesc('published_at')
            ->limit(3)
            ->pluck('id')
            ->all();

        $blocks = $this->buildBlocks(
            luxury: $luxury,
            onyx: $onyx,
            bestsellerIds: $bestsellers,
            newIds: $newItems,
            articleIds: $articleIds,
        );

        Page::query()->updateOrCreate(
            ['is_homepage' => true],
            [
                'slug' => ['uk' => 'home-uk', 'en' => 'home-en'],
                'title' => ['uk' => 'Головна', 'en' => 'Home'],
                'intro' => ['uk' => '', 'en' => ''],
                'content' => null,
                'seo_title' => [
                    'uk' => 'Levant Parfums · Нішевий аромат. Чесна ціна.',
                    'en' => 'Levant Parfums · Niche fragrance. Honest price.',
                ],
                'seo_description' => [
                    'uk' => 'Levant - 22 композиції у двох колекціях. Розроблено в Іспанії, розлито в Туреччині, зібрано в Україні.',
                    'en' => 'Levant - 22 compositions in two collections. Composed in Spain, bottled in Turkey, assembled in Ukraine.',
                ],
                'is_published' => true,
                'template' => PageTemplate::Landing,
                'blocks' => $blocks,
            ],
        );

        $simpleDefaults = [
            'is_published' => true,
            'template' => PageTemplate::Simple,
            'is_homepage' => false,
            'blocks' => null,
        ];

        foreach ($this->simplePages() as $page) {
            $data = array_merge($simpleDefaults, $page);
            $ukSlug = $data['slug']['uk'];
            $existing = Page::query()->whereJsonContains('slug->uk', $ukSlug)->first();
            if ($existing) {
                $existing->fill($data)->save();
            } else {
                Page::query()->create($data);
            }
        }
    }

    private function simplePages(): array
    {
        $slugs = config('content.help_pages');

        return [
            [
                'slug' => $slugs['delivery'],
                'title' => ['uk' => 'Доставка та оплата', 'en' => 'Delivery & payment'],
                'intro' => [
                    'uk' => 'Як ми відправляємо ваше замовлення та які способи оплати приймаємо.',
                    'en' => 'How we ship your order and which payment methods we accept.',
                ],
                'content' => [
                    'uk' => <<<'MD'
## Доставка

Ми працюємо з **Новою Поштою** по всій Україні. Замовлення, оформлені до 14:00, відправляємо того самого дня; решта - наступного робочого дня.

- Доставка у відділення або поштомат
- Кур'єрська доставка за адресою
- **Безкоштовно** при сумі замовлення від 1 500 грн

## Оплата

- Накладений платіж при отриманні
- Оплата карткою онлайн (Visa, Mastercard) через захищений шлюз
- Безготівковий розрахунок для юридичних осіб

Якщо потрібна додаткова інформація - напишіть нам на [concierge@levant.parfum](mailto:concierge@levant.parfum).
MD,
                    'en' => <<<'MD'
## Delivery

We ship across Ukraine via **Nova Poshta**. Orders placed before 14:00 leave the warehouse the same day; later orders ship the next business day.

- Branch or parcel-locker pickup
- Door-to-door courier delivery
- **Free shipping** for orders over UAH 1,500

## Payment

- Cash on delivery
- Online card payment (Visa, Mastercard) via a secure gateway
- Bank transfer for legal entities

Need more information? Write to us at [concierge@levant.parfum](mailto:concierge@levant.parfum).
MD,
                ],
                'seo_title' => [
                    'uk' => 'Доставка та оплата · Levant Parfums',
                    'en' => 'Delivery & payment · Levant Parfums',
                ],
                'seo_description' => [
                    'uk' => 'Доставка Новою Поштою по Україні. Безкоштовно від 1 500 грн. Оплата карткою, накладений платіж або безготівково.',
                    'en' => 'Nova Poshta delivery across Ukraine. Free over UAH 1,500. Pay by card, cash on delivery, or bank transfer.',
                ],
            ],

            [
                'slug' => $slugs['returns'],
                'title' => ['uk' => 'Повернення', 'en' => 'Returns'],
                'intro' => [
                    'uk' => 'Якщо аромат вам не підійшов - ми повернемо кошти протягом 14 днів.',
                    'en' => 'If the scent does not suit you, we refund your purchase within 14 days.',
                ],
                'content' => [
                    'uk' => <<<'MD'
## 14 днів на повернення

Згідно з законодавством України, ви маєте право повернути товар протягом **14 днів** з моменту отримання, якщо він не був у використанні та зберіг товарний вигляд.

## Як оформити повернення

1. Напишіть нам на [concierge@levant.parfum](mailto:concierge@levant.parfum) із номером замовлення.
2. Ми надішлемо інструкції та адресу для відправлення.
3. Після огляду товару кошти повертаємо протягом 5 робочих днів на ту саму платіжну картку чи рахунок.

Витрати на зворотну пересилку покриває покупець, окрім випадків браку або помилки з нашого боку.
MD,
                    'en' => <<<'MD'
## 14-day return window

Under Ukrainian law you may return any item within **14 days** of receipt, provided it has not been used and retains its original packaging.

## How to start a return

1. Email us at [concierge@levant.parfum](mailto:concierge@levant.parfum) with your order number.
2. We will reply with instructions and the return address.
3. After inspection we refund within 5 business days to your original card or account.

Return shipping is paid by the customer, except for defective items or our own mistakes.
MD,
                ],
                'seo_title' => [
                    'uk' => 'Повернення · Levant Parfums',
                    'en' => 'Returns · Levant Parfums',
                ],
                'seo_description' => [
                    'uk' => 'Повернення товару протягом 14 днів. Швидке оформлення, повернення коштів за 5 робочих днів.',
                    'en' => '14-day return window. Easy process, refund within 5 business days.',
                ],
            ],

            [
                'slug' => $slugs['terms'],
                'title' => ['uk' => 'Угода користувача', 'en' => 'Terms of service'],
                'intro' => [
                    'uk' => 'Умови, на яких ми пропонуємо товари та послуги через сайт Levant Parfums.',
                    'en' => 'The terms under which we offer products and services through the Levant Parfums website.',
                ],
                'content' => [
                    'uk' => <<<'MD'
## Загальні положення

Користуючись сайтом Levant Parfums, ви погоджуєтесь із наведеними нижче умовами. Якщо ви не згодні з якимось пунктом - просимо не використовувати сайт.

## Замовлення та оплата

Усі ціни вказані у гривнях та євро та включають ПДВ. Ми залишаємо за собою право змінювати ціни та асортимент без попереднього повідомлення; уже оформлені замовлення обробляються за цінами на момент оформлення.

## Відповідальність

Levant Parfums не несе відповідальності за непрямі збитки, що виникли внаслідок використання сайту. Усі товари сертифіковані та відповідають вимогам безпеки.

## Контакти

З питань, пов'язаних з угодою, звертайтеся: [concierge@levant.parfum](mailto:concierge@levant.parfum).
MD,
                    'en' => <<<'MD'
## General

By using the Levant Parfums website you agree to the terms below. If you do not accept any part of them, please do not use the site.

## Orders and payment

All prices are listed in UAH and EUR and include VAT. We reserve the right to change prices and the assortment without prior notice; orders already placed are processed at the price valid at the time of checkout.

## Liability

Levant Parfums is not liable for indirect damages arising from the use of the site. All products are certified and meet applicable safety requirements.

## Contact

For questions about these terms, write to [concierge@levant.parfum](mailto:concierge@levant.parfum).
MD,
                ],
                'seo_title' => [
                    'uk' => 'Угода користувача · Levant Parfums',
                    'en' => 'Terms of service · Levant Parfums',
                ],
                'seo_description' => [
                    'uk' => 'Умови використання сайту Levant Parfums, оформлення замовлень та відповідальність сторін.',
                    'en' => 'Terms of use for the Levant Parfums website, ordering rules and liability.',
                ],
            ],

            [
                'slug' => $slugs['privacy'],
                'title' => ['uk' => 'Політика конфіденційності', 'en' => 'Privacy policy'],
                'intro' => [
                    'uk' => 'Як ми збираємо, використовуємо та зберігаємо ваші персональні дані.',
                    'en' => 'How we collect, use and store your personal data.',
                ],
                'content' => [
                    'uk' => <<<'MD'
## Які дані ми збираємо

Для обробки замовлень нам потрібні ваше ім'я, контактний телефон, адреса доставки та email. Ми не збираємо більше, ніж необхідно для виконання замовлення та законодавчих вимог.

## Як ми використовуємо дані

- Виконання та доставка замовлень
- Зв'язок із вами щодо статусу замовлення
- Відправка інформаційних розсилок - **лише за вашою згодою**

## Зберігання та захист

Дані зберігаються на захищених серверах у межах ЄС. Ми не передаємо ваші дані третім особам, окрім служб доставки та платіжних провайдерів, необхідних для виконання замовлення.

## Ваші права

Ви маєте право у будь-який момент отримати, виправити чи видалити ваші персональні дані. Запит надсилайте на [concierge@levant.parfum](mailto:concierge@levant.parfum).
MD,
                    'en' => <<<'MD'
## What we collect

To process your order we need your name, phone number, shipping address and email. We do not collect more than is required to fulfil the order and meet legal obligations.

## How we use the data

- Processing and delivering your order
- Contacting you about order status
- Sending newsletters - **only with your explicit consent**

## Storage and protection

Data is stored on secure servers within the EU. We do not share it with third parties other than the delivery and payment providers required to fulfil your order.

## Your rights

You may request access to, correction of, or deletion of your personal data at any time by writing to [concierge@levant.parfum](mailto:concierge@levant.parfum).
MD,
                ],
                'seo_title' => [
                    'uk' => 'Політика конфіденційності · Levant Parfums',
                    'en' => 'Privacy policy · Levant Parfums',
                ],
                'seo_description' => [
                    'uk' => 'Як Levant Parfums збирає, використовує та захищає персональні дані клієнтів.',
                    'en' => 'How Levant Parfums collects, uses and protects customer personal data.',
                ],
            ],
        ];
    }


    private function copyAsset(string $filename): void
    {
        $target = "pages/blocks/{$filename}";
        if (Storage::disk('public')->exists($target)) {
            return;
        }
        $source = database_path("seeders/images/{$filename}");
        if (! file_exists($source)) {
            return;
        }
        Storage::disk('public')->put($target, file_get_contents($source));
    }

    private function buildBlocks(?Series $luxury, ?Series $onyx, array $bestsellerIds, array $newIds, array $articleIds): array
    {
        return [
            [
                'type' => 'hero',
                'data' => [
                    'is_visible' => true,
                    'eyebrow' => [
                        'uk' => 'Колекція 2026 · Luxury × Onyx',
                        'en' => 'Collection 2026 · Luxury × Onyx',
                    ],
                    'title_top' => [
                        'uk' => 'Нішевий аромат.',
                        'en' => 'Niche fragrance.',
                    ],
                    'title_bottom' => [
                        'uk' => 'Чесна ціна.',
                        'en' => 'Honest price.',
                    ],
                    'lead' => [
                        'uk' => 'Levant - це 22 композиції у двох колекціях: Luxury Series та Onyx Series. Розроблено в Іспанії, розлито в Туреччині, ринок і душа - в Україні. Без переплати за логотип.',
                        'en' => 'Levant is 22 compositions in two collections - Luxury Series and Onyx Series. Composed in Spain, bottled in Turkey, the market and the soul in Ukraine. No premium for a logo.',
                    ],
                    'floating_label' => [
                        'uk' => 'Іспанія → Туреччина → Україна',
                        'en' => 'Spain → Turkey → Ukraine',
                    ],
                    'cta_label' => ['uk' => 'Дослідити каталог', 'en' => 'Explore the catalogue'],
                    'cta_url' => '/products',
                    'secondary_cta_label' => ['uk' => 'Філософія дому', 'en' => 'Our philosophy'],
                    'secondary_cta_url' => '#manifesto',
                    'image_path' => 'pages/blocks/levant-luxury-bottle.jpg',
                    'meta' => [
                        ['num' => '22', 'meta_label' => ['uk' => 'Композиції', 'en' => 'Compositions']],
                        ['num' => '2', 'meta_label' => ['uk' => 'Серії', 'en' => 'Series']],
                        ['num' => '3', 'meta_label' => ['uk' => 'Країни', 'en' => 'Countries']],
                    ],
                ],
            ],

            [
                'type' => 'text',
                'data' => [
                    'is_visible' => true,
                    'anchor' => 'manifesto',
                    'eyebrow' => ['uk' => 'Філософія', 'en' => 'Philosophy'],
                    'title' => [
                        'uk' => "Якщо вам потрібне ім'я і красива коробка - вам у дьюті-фрі.",
                        'en' => 'If you need a name and a beautiful box - go to duty-free.',
                    ],
                    'body' => [
                        'uk' => "Ми ж - для тих, хто хоче аромат, а не бирку. 20 років у парфумерії дають нам знати, де купувати найкращі інгредієнти, - і ми це робимо.\n\nРозроблено в Іспанії. Розлито в Туреччині. Зібрано тут - в Україні. Без переплати за логотип, без подвоєної ціни за порожній флакон.",
                        'en' => "We are for those who want the scent, not the tag. Twenty years in perfumery taught us where to source the best ingredients - and we do.\n\nComposed in Spain. Bottled in Turkey. Assembled here, in Ukraine. No premium for a logo, no doubled price for an empty bottle.",
                    ],
                    'signature' => ['uk' => '- Команда Levant', 'en' => '- The Levant team'],
                ],
            ],

            [
                'type' => 'products',
                'data' => [
                    'is_visible' => ! empty($bestsellerIds),
                    'eyebrow' => ['uk' => 'Бестселери', 'en' => 'Bestsellers'],
                    'title' => ['uk' => 'Найулюбленіші у 2026', 'en' => 'Most loved in 2026'],
                    'cta_label' => ['uk' => 'Усі бестселери', 'en' => 'All bestsellers'],
                    'cta_url' => '/products?sort=pop',
                    'items' => array_map(fn ($id) => ['product_id' => $id], $bestsellerIds),
                ],
            ],

            [
                'type' => 'brand_story',
                'data' => [
                    'is_visible' => true,
                    'eyebrow' => ['uk' => 'Три точки. Один путь.', 'en' => 'Three points. One signature.'],
                    'title' => [
                        'uk' => 'Levant - перетин трьох світів',
                        'en' => 'Levant - a crossing of three worlds',
                    ],
                    'body' => [
                        'uk' => 'Levant - давня назва регіону, де зустрічаються Схід і Захід, де торгівля, культура та аромати знаходили одне одного тисячоліттями.',
                        'en' => 'Levant is the ancient name of a region where East and West meet, where trade, culture and scent have found each other for millennia.',
                    ],
                    'pillars' => [
                        [
                            'pillar_label' => ['uk' => 'Іспанія', 'en' => 'Spain'],
                            'pillar_caption' => ['uk' => 'Народження ідеї', 'en' => 'Where the idea is born'],
                        ],
                        [
                            'pillar_label' => ['uk' => 'Туреччина', 'en' => 'Turkey'],
                            'pillar_caption' => ['uk' => 'Розливається тут', 'en' => 'Where it is bottled'],
                        ],
                        [
                            'pillar_label' => ['uk' => 'Україна', 'en' => 'Ukraine'],
                            'pillar_caption' => ['uk' => 'Ринок і душа', 'en' => 'The market and the soul'],
                        ],
                    ],
                ],
            ],

            [
                'type' => 'series_duo',
                'data' => [
                    'is_visible' => true,
                    'eyebrow' => ['uk' => 'Колекції', 'en' => 'Collections'],
                    'title' => ['uk' => 'Дві серії, одна філософія', 'en' => 'Two series, one philosophy'],
                    'items' => [
                        [
                            'series_id' => $luxury?->id,
                            'image_path' => 'pages/blocks/levant-flacon-3.jpg',
                            'kicker' => ['uk' => '17 ароматів · жіноча та унісекс', 'en' => '17 fragrances · women & unisex'],
                            'title' => ['uk' => 'Luxury Series', 'en' => 'Luxury Series'],
                            'description' => [
                                'uk' => 'Від легкого флорального шепоту до насиченої нішевої глибини. Жіноча й унісекс - для тієї, яка знає, чого хоче.',
                                'en' => 'From a light floral whisper to a deep niche core. Women and unisex - for those who know what they want.',
                            ],
                            'cta_label' => ['uk' => 'Перейти до серії', 'en' => 'Open the series'],
                        ],
                        [
                            'series_id' => $onyx?->id,
                            'image_path' => 'pages/blocks/levant-flacon-4.jpg',
                            'kicker' => ['uk' => '5 ароматів · чоловіча колекція', 'en' => "5 fragrances · men's collection"],
                            'title' => ['uk' => 'Onyx Series', 'en' => 'Onyx Series'],
                            'description' => [
                                'uk' => 'Для чоловіків, які не шукають уваги - і саме тому її отримують. Мінеральна глибина оніксу, вечірня стійкість, підпис без слів.',
                                'en' => 'For men who do not seek attention - and receive it precisely for that. The mineral depth of onyx, evening tenacity, a signature without words.',
                            ],
                            'cta_label' => ['uk' => 'Перейти до серії', 'en' => 'Open the series'],
                        ],
                    ],
                ],
            ],

            [
                'type' => 'pillars',
                'data' => [
                    'is_visible' => true,
                    'surface' => 'tinted',
                    'eyebrow' => ['uk' => 'Гід', 'en' => 'Guide'],
                    'title' => ['uk' => 'Знайдіть аромат за три кроки', 'en' => 'Find a scent in three steps'],
                    'body' => ['uk' => '', 'en' => ''],
                    'items' => [
                        [
                            'eyebrow' => ['uk' => '01 · Сімейство', 'en' => '01 · Family'],
                            'title' => ['uk' => 'Сімейство', 'en' => 'Family'],
                            'body' => [
                                'uk' => 'Квіткові, східні, деревні, шкіряні, шипрові, зелені, гурманні. З чим ви проводите день?',
                                'en' => 'Floral, oriental, woody, leather, chypre, green, gourmand. What is the air like in your day?',
                            ],
                        ],
                        [
                            'eyebrow' => ['uk' => '02 · Інтенсивність', 'en' => '02 · Intensity'],
                            'title' => ['uk' => 'Інтенсивність', 'en' => 'Intensity'],
                            'body' => [
                                'uk' => 'Шлейф від інтимного до галерейного. Оберіть, наскільки вас має бути чути.',
                                'en' => 'Trail from intimate to gallery. Choose how loudly you want to be heard.',
                            ],
                        ],
                        [
                            'eyebrow' => ['uk' => '03 · Випадок', 'en' => '03 · Occasion'],
                            'title' => ['uk' => 'Випадок', 'en' => 'Occasion'],
                            'body' => [
                                'uk' => 'Робочий ранок, вечірня сукня, щоденне. Парфум знає різницю.',
                                'en' => 'Working morning, evening dress, everyday. Perfume knows the difference.',
                            ],
                        ],
                    ],
                ],
            ],

            [
                'type' => 'products',
                'data' => [
                    'is_visible' => ! empty($newIds),
                    'eyebrow' => ['uk' => 'Новинки', 'en' => 'New arrivals'],
                    'title' => ['uk' => 'Свіже з лабораторії', 'en' => 'Just out of the lab'],
                    'cta_label' => ['uk' => 'Усі новинки', 'en' => 'All new'],
                    'cta_url' => '/products?sort=new',
                    'items' => array_map(fn ($id) => ['product_id' => $id], $newIds),
                ],
            ],

            [
                'type' => 'pillars',
                'data' => [
                    'is_visible' => true,
                    'surface' => 'default',
                    'eyebrow' => ['uk' => 'Чому Levant', 'en' => 'Why Levant'],
                    'title' => ['uk' => 'Три причини обрати Levant', 'en' => 'Three reasons to choose Levant'],
                    'body' => ['uk' => '', 'en' => ''],
                    'items' => [
                        [
                            'eyebrow' => ['uk' => '', 'en' => ''],
                            'title' => ['uk' => 'Розроблено в Іспанії', 'en' => 'Composed in Spain'],
                            'body' => [
                                'uk' => '20 років досвіду парфумерної школи. Доступ до найкращих інгредієнтів - і ми це знаємо.',
                                'en' => 'Twenty years of perfumery school. Access to the best ingredients - and we know how to use them.',
                            ],
                        ],
                        [
                            'eyebrow' => ['uk' => '', 'en' => ''],
                            'title' => ['uk' => 'Доставка Новою Поштою', 'en' => 'Nova Poshta delivery'],
                            'body' => [
                                'uk' => 'Безкоштовно від 1 500 грн. Відправлення у день замовлення, якщо до 14:00.',
                                'en' => 'Free over 1 500 UAH. Same-day shipping if ordered before 14:00.',
                            ],
                        ],
                        [
                            'eyebrow' => ['uk' => '', 'en' => ''],
                            'title' => ['uk' => 'Повернення за 14 днів', 'en' => '14-day returns'],
                            'body' => [
                                'uk' => 'Якщо аромат не той - повернемо кошти без зайвих питань.',
                                'en' => 'If the scent is not yours - we refund without questions.',
                            ],
                        ],
                    ],
                ],
            ],

            [
                'type' => 'testimonials',
                'data' => [
                    'is_visible' => true,
                    'eyebrow' => ['uk' => 'Відгуки', 'en' => 'Reviews'],
                    'title' => ['uk' => 'Що пишуть про Levant', 'en' => 'What is said about Levant'],
                    'cta_label' => ['uk' => 'Усі відгуки', 'en' => 'All reviews'],
                    'cta_url' => '#',
                    'items' => [
                        [
                            'quote' => [
                                'uk' => 'Onyx № 03 - це той випадок, коли тобі не треба представлятись. Шлейф розповідає за тебе.',
                                'en' => 'Onyx № 03 is the case when you do not need to introduce yourself. The trail does it for you.',
                            ],
                            'author' => 'Софія К.',
                            'city' => ['uk' => 'Київ', 'en' => 'Kyiv'],
                            'rating' => 5,
                        ],
                        [
                            'quote' => [
                                'uk' => 'Замовив Luxury № 02 у подарунок. Дружина впізнала аромат, але здивувалась ціні.',
                                'en' => 'I ordered Luxury № 02 as a gift. My wife recognised the scent - and was surprised by the price.',
                            ],
                            'author' => 'Тарас М.',
                            'city' => ['uk' => 'Львів', 'en' => 'Lviv'],
                            'rating' => 5,
                        ],
                        [
                            'quote' => [
                                'uk' => 'Перейшла на Levant з нішевих французьких марок і не шкодую. Якість на тому ж рівні, ціна - нижча.',
                                'en' => 'I moved from French niche houses to Levant - no regrets. Same quality, a lower price.',
                            ],
                            'author' => 'Анна П.',
                            'city' => ['uk' => 'Одеса', 'en' => 'Odesa'],
                            'rating' => 5,
                        ],
                        [
                            'quote' => [
                                'uk' => 'Onyx № 05 - найкращий зимовий аромат у моїй колекції. Тютюн і амбра - це геніально.',
                                'en' => 'Onyx № 05 - the best winter scent in my collection. Tobacco and amber - genius.',
                            ],
                            'author' => 'Іван Р.',
                            'city' => ['uk' => 'Харків', 'en' => 'Kharkiv'],
                            'rating' => 5,
                        ],
                    ],
                ],
            ],

            [
                'type' => 'articles',
                'data' => [
                    'is_visible' => count($articleIds) >= 3,
                    'eyebrow' => ['uk' => 'Журнал', 'en' => 'Journal'],
                    'title' => [
                        'uk' => 'Свіже з нашого редакторського столу',
                        'en' => 'Fresh from our editorial desk',
                    ],
                    'cta_label' => ['uk' => 'Усі статті', 'en' => 'All articles'],
                    'cta_url' => '#',
                    'items' => array_map(fn ($id) => ['article_id' => $id], array_slice($articleIds, 0, 3)),
                ],
            ],
        ];
    }
}
