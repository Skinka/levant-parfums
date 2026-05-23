<?php

return [
    'navigation' => [
        'group' => 'Submissions',
    ],

    'resource' => [
        'label' => 'Submission',
        'plural' => 'Submissions',
    ],

    'types' => [
        'contact' => 'Contact request',
        'order' => 'Order request',
    ],

    'statuses' => [
        'new' => 'New',
        'read' => 'Read',
        'processed' => 'Processed',
    ],

    'actions' => [
        'mark_read' => 'Mark as read',
        'mark_processed' => 'Mark as processed',
        'mark_new' => 'Move back to «New»',
    ],

    'fields' => [
        'type' => 'Type',
        'status' => 'Status',
        'subject' => 'Subject',
        'summary' => 'Summary',
        'data' => 'Form data',
        'meta' => 'Technical metadata',
        'locale' => 'Locale',
        'created_at' => 'Created at',
        'handled_at' => 'Processed at',
        'name' => 'Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'message' => 'Message',
        'qty' => 'Quantity',
        'note' => 'Note',
    ],

    'errors' => [
        'rate_limited' => 'Too many attempts. Please try again later.',
    ],

    'notifications' => [
        'new' => 'New submission: :type',
    ],

    'mail' => [
        'contact' => [
            'admin' => [
                'subject' => 'New contact request',
                'intro' => 'A new contact request has arrived.',
            ],
        ],
        'order' => [
            'admin' => [
                'subject' => 'New order request',
                'intro' => 'A new order request has arrived.',
            ],
            'client' => [
                'subject' => 'We received your order request',
                'intro' => 'Thank you — we received your request and will get back to you shortly.',
            ],
        ],
    ],
];
