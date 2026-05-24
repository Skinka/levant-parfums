<?php

use App\Forms\Types\ContactFormType;
use App\Forms\Types\OrderFormType;

return [
    'admin_email' => env('FORMS_ADMIN_EMAIL'),

    'types' => [
        ContactFormType::class,
        OrderFormType::class,
    ],

    'queue' => env('FORMS_QUEUE', null),

    'statuses' => ['new', 'read', 'processed'],
];
