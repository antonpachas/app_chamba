<?php

return [
    'features' => [
        'escrow' => env('CHAMBA_FEATURE_ESCROW', false),
        'subscriptions' => env('CHAMBA_FEATURE_SUBSCRIPTIONS', true),
    ],

    'commission' => [
        'default_rate' => env('CHAMBA_COMMISSION_RATE', 15.00),
    ],

    'payouts' => [
        'platform_yape' => env('CHAMBA_PLATFORM_YAPE', '999999999'),
        'platform_bank_name' => env('CHAMBA_PLATFORM_BANK_NAME', 'BCP'),
        'platform_bank_account' => env('CHAMBA_PLATFORM_BANK_ACCOUNT', '000-00000000-0-00'),
        'platform_bank_holder' => env('CHAMBA_PLATFORM_BANK_HOLDER', 'Chamba S.A.C.'),
    ],

    'subscriptions' => [
        'provider' => [
            'pro_price' => env('CHAMBA_PROVIDER_PRO_PRICE', 29.00),
            'trial_days' => env('CHAMBA_PROVIDER_TRIAL_DAYS', 30),
            'free_contacts_per_month' => env('CHAMBA_FREE_CONTACTS_PER_MONTH', 3),
            'free_max_services' => env('CHAMBA_FREE_MAX_SERVICES', 1),
            'pro_max_services' => env('CHAMBA_PRO_MAX_SERVICES', 20),
        ],
        'client' => [
            'premium_price' => env('CHAMBA_CLIENT_PREMIUM_PRICE', 9.00),
            'trial_days' => env('CHAMBA_CLIENT_TRIAL_DAYS', 0),
        ],
        'grace_days' => env('CHAMBA_GRACE_DAYS', 5),
        'currency' => env('CHAMBA_CURRENCY', 'PEN'),
    ],

    'media' => [
        'disk' => env('CHAMBA_MEDIA_DISK', 'chamba_ftp'),
        'max_kb' => env('CHAMBA_MEDIA_MAX_KB', 5120),
        'public_url' => env('CHAMBA_FTP_PUBLIC_URL', ''),
    ],
];
