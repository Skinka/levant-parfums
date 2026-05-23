<?php

return [
    'admin_email' => env('FORMS_ADMIN_EMAIL'),

    'types' => [
        \App\Forms\Types\ContactFormType::class,
        \App\Forms\Types\OrderFormType::class,
    ],

    'queue' => env('FORMS_QUEUE', null),

    'statuses' => ['new', 'read', 'processed'],
];
