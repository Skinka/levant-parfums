<?php

return [
    'navigation' => [
        'group' => 'Content',
        'pages' => 'Pages',
        'articles' => 'Articles',
    ],
    'page' => [
        'singular' => 'Page',
        'plural' => 'Pages',
    ],
    'article' => [
        'singular' => 'Article',
        'plural' => 'Articles',
    ],
    'tabs' => [
        'main' => 'Main',
        'seo' => 'SEO',
        'images' => 'Images',
    ],
    'fields' => [
        'title' => 'Title',
        'slug' => 'URL',
        'intro' => 'Intro',
        'category' => 'Category',
        'read_time_minutes' => 'Read time (min)',
        'content' => 'Content',
        'seo_title' => 'SEO title',
        'seo_description' => 'SEO description',
        'is_published' => 'Published',
        'published_at' => 'Publish at',
        'primary' => 'Primary image',
        'products' => 'Related products',
        'add_product' => 'Add product',
        'product_id' => 'Product',
        'template' => 'Template',
        'blocks' => 'Page blocks',
        'add_block' => 'Add block',
        'is_homepage' => 'Homepage',
    ],
    'hints' => [
        'published_at' => 'The article will appear on the site at this time. Leave empty to publish immediately.',
    ],
    'actions' => [
        'publish' => 'Publish',
        'unpublish' => 'Unpublish',
    ],
    'filters' => [
        'scheduled' => 'Scheduled',
    ],
    'template' => [
        'simple' => 'Simple page',
        'landing' => 'Landing (blocks)',
    ],
    'units' => [
        'minutes' => 'min',
    ],
    'blocks' => [
        'hero' => [
            'label' => 'Hero block',
        ],
        'products' => [
            'label' => 'Product list',
            'add_item' => 'Add product',
        ],
        'text' => [
            'label' => 'Text block',
        ],
        'articles' => [
            'label' => 'Article list',
            'add_item' => 'Add article',
        ],
        'fields' => [
            'is_visible' => 'Show block',
            'anchor' => 'Anchor (URL id)',
            'title' => 'Title',
            'subtitle' => 'Subtitle',
            'body' => 'Body',
            'cta_label' => 'CTA label',
            'cta_url' => 'CTA URL',
            'image_path' => 'Image',
            'product_id' => 'Product',
            'article_id' => 'Article',
            'cta_url_helper' => 'Internal "/products" or absolute "https://..." both work.',
        ],
    ],
];
