<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Plans
    |--------------------------------------------------------------------------
    |
    | Each plan key MUST match a case of Domain\Workspaces\Enums\WorkspacePlan.
    | Free has no Paddle price (no subscription is created — the workspace
    | simply has plan='free'). Premium and Enterprise reference Paddle price
    | IDs from the Paddle dashboard (sandbox or live, controlled by
    | PADDLE_SANDBOX).
    |
    */

    'plans' => [

        'basic' => [
            'name' => 'Basic',
            'price_id' => env('PADDLE_PRICE_BASIC'),
            'amount' => 1000,
            'features' => [
                '10 conversions / month',
                'PDF, Word doc, slide deck, spreadsheets',
                'Fair rate limits',
            ],
        ],

        'pro' => [
            'name' => 'Pro',
            'price_id' => env('PADDLE_PRICE_PRO'),
            'amount' => 10000,
            'features' => [
                'Everything in Free',
                '100 conversions / month',
                'Audio transcription * Coming Soon',
                'Video transcription * Coming Soon',
                'Priority email support',
            ],
        ],

        'enterprise' => [
            'name' => 'Enterprise',
            'price_id' => env('PADDLE_PRICE_ENTERPRISE'),
            'amount' => 50000,
            'features' => [
                'Everything in Premium',
                '500 conversions / month',
                'SSO + audit logs',
                'Dedicated support',
            ],
        ],
    ],
];
