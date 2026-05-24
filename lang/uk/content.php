<?php

return [
    'navigation' => [
        'group' => 'Контент',
        'pages' => 'Сторінки',
        'articles' => 'Статті',
    ],
    'page' => [
        'singular' => 'Сторінка',
        'plural' => 'Сторінки',
    ],
    'article' => [
        'singular' => 'Стаття',
        'plural' => 'Статті',
    ],
    'tabs' => [
        'main' => 'Основне',
        'seo' => 'SEO',
        'images' => 'Зображення',
    ],
    'fields' => [
        'title' => 'Заголовок',
        'slug' => 'URL',
        'intro' => 'Короткий вступ',
        'content' => 'Контент',
        'seo_title' => 'SEO title',
        'seo_description' => 'SEO description',
        'is_published' => 'Опубліковано',
        'published_at' => 'Дата публікації',
        'primary' => 'Основне зображення',
        'products' => 'Прив’язані товари',
        'add_product' => 'Додати товар',
        'product_id' => 'Товар',
        'template' => 'Шаблон',
        'blocks' => 'Блоки сторінки',
        'add_block' => 'Додати блок',
        'is_homepage' => 'Головна сторінка',
    ],
    'hints' => [
        'published_at' => "Стаття з'явиться на сайті в цей час. Лишіть порожнім — публікація одразу.",
    ],
    'actions' => [
        'publish' => 'Опублікувати',
        'unpublish' => 'Зняти з публікації',
    ],
    'filters' => [
        'scheduled' => 'Заплановані',
    ],
    'template' => [
        'simple' => 'Звичайна сторінка',
        'landing' => 'Лендинг (блоки)',
    ],
    'blocks' => [
        'hero' => [
            'label' => 'Hero-блок',
        ],
        'text' => [
            'label' => 'Маніфест / редакторський текст',
        ],
        'products' => [
            'label' => 'Список товарів',
            'add_item' => 'Додати товар',
        ],
        'brand_story' => [
            'label' => 'Бренд-сторі (три точки)',
            'add_pillar' => 'Додати точку',
        ],
        'series_duo' => [
            'label' => 'Дві колекції (Onyx × Luxury)',
            'add_item' => 'Додати колекцію',
        ],
        'pillars' => [
            'label' => 'Гайд / Переваги (нумерована сітка)',
            'add_item' => 'Додати пункт',
        ],
        'testimonials' => [
            'label' => 'Відгуки (слайдер)',
            'add_item' => 'Додати відгук',
        ],
        'articles' => [
            'label' => 'Список статей',
            'add_item' => 'Додати статтю',
        ],
        'fields' => [
            // existing common
            'is_visible' => 'Показувати блок',
            'anchor' => 'Якір (id у URL)',

            // existing block-level (used widely)
            'title' => 'Заголовок',
            'subtitle' => 'Підзаголовок',
            'body' => 'Текст',
            'cta_label' => 'Текст кнопки',
            'cta_url' => 'Посилання кнопки',
            'image_path' => 'Зображення',
            'product_id' => 'Товар',
            'article_id' => 'Стаття',
            'cta_url_helper' => 'Можна вписати внутрішнє "/products" або зовнішнє "https://...".',

            // NEW common across blocks
            'eyebrow' => 'Eyebrow (надзаголовок)',
            'lead' => 'Ліб (короткий абзац)',
            'signature' => 'Підпис',
            'kicker' => 'Кікер',
            'description' => 'Опис',
            'surface' => 'Фон секції',

            // hero
            'title_top' => 'Заголовок (верхній рядок)',
            'title_bottom' => 'Заголовок (нижній рядок, курсив)',
            'floating_label' => 'Парящий ярлик',
            'meta' => 'Метрики (3 числа)',
            'meta_num' => 'Число',
            'meta_label' => 'Підпис',
            'secondary_cta_label' => 'Текст другої кнопки',
            'secondary_cta_url' => 'Посилання другої кнопки',

            // brand_story
            'pillars' => 'Точки',
            'pillar_label' => 'Назва',
            'pillar_caption' => 'Підпис',

            // series_duo
            'series_id' => 'Серія',

            // testimonials
            'quote' => 'Цитата',
            'author' => 'Автор',
            'city' => 'Місто',
            'rating' => 'Оцінка (1-5)',
        ],
        'surface' => [
            'default' => 'Без фону (default)',
            'tinted' => 'Затемнений фон (tinted)',
        ],
    ],
];
