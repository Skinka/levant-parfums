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
        'city' => 'City',
        'np_office' => 'Nova Poshta office',
        'comment' => 'Comment',
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
        'preorder' => [
            'admin' => [
                'subject' => 'New preorder',
                'intro' => 'A new preorder has arrived (item temporarily out of stock).',
            ],
            'client' => [
                'subject' => 'We received your preorder',
                'intro' => 'Thank you. This is a preorder — we will reach out as soon as the item is ready to ship.',
            ],
        ],
    ],

    'order' => [
        'preorder_admin_notice' => 'Item is temporarily out of stock — this is a preorder.',
        'preorder_client_notice' => 'The item is temporarily out of stock. We will reserve your preorder and let you know when it is ready.',
        'eyebrow' => ['order' => 'Order', 'preorder' => 'Preorder'],
        'title' => ['order' => 'Place an order', 'preorder' => 'Place a preorder'],
        'intro' => [
            'order' => 'Leave your contacts — we will reach out within the day to confirm.',
            'preorder' => 'Leave your contacts — we will reserve the item and contact you about timing.',
        ],
        'submit' => ['order' => 'Place order', 'preorder' => 'Place preorder'],
        'agree' => 'By clicking, I agree with the privacy policy',
        'thanks' => ['order' => 'Thank you for your order', 'preorder' => 'Thank you for your preorder'],
        'subtotal' => 'Subtotal',
    ],
];
