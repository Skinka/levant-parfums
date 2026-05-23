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
        'products' => [
            'label' => 'Список товарів',
            'add_item' => 'Додати товар',
        ],
        'text' => [
            'label' => 'Текстовий блок',
        ],
        'articles' => [
            'label' => 'Список статей',
            'add_item' => 'Додати статтю',
        ],
        'fields' => [
            'is_visible' => 'Показувати блок',
            'anchor' => 'Якір (id у URL)',
            'title' => 'Заголовок',
            'subtitle' => 'Підзаголовок',
            'body' => 'Текст',
            'cta_label' => 'Текст кнопки',
            'cta_url' => 'Посилання кнопки',
            'image_path' => 'Зображення',
            'product_id' => 'Товар',
            'article_id' => 'Стаття',
            'cta_url_helper' => 'Можна вписати внутрішнє "/products" або зовнішнє "https://...".',
        ],
    ],
];
