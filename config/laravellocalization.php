<?php

return [

    'supportedLocales' => [
        'uk' => ['name' => 'Ukrainian', 'script' => 'Cyrl', 'native' => 'Українська', 'regional' => 'uk_UA'],
        'en' => ['name' => 'English',   'script' => 'Latn', 'native' => 'English',    'regional' => 'en_GB'],
    ],

    'useAcceptLanguageHeader' => true,

    'hideDefaultLocaleInURL' => true,

    'localesOrder' => [],

    'localesMapping' => [],

    'utf8suffix' => env('LARAVELLOCALIZATION_UTF8SUFFIX', '.UTF-8'),

    'urlsIgnored' => [],

    'httpMethodsIgnored' => ['POST', 'PUT', 'PATCH', 'DELETE'],
];
