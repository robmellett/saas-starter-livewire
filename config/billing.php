<?php

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

        'free' => [
            'name' => 'Free',
            'price_id' => null,
            'amount' => 0,
            'features' => [
                'Single workspace',
                'Up to 3 members',
                'Community support',
            ],
        ],

        'premium' => [
            'name' => 'Premium',
            'price_id' => env('PADDLE_PRICE_PREMIUM'),
            'amount' => 1900,
            'features' => [
                'Everything in Free',
                'Up to 25 members',
                'Priority email support',
            ],
        ],

        'enterprise' => [
            'name' => 'Enterprise',
            'price_id' => env('PADDLE_PRICE_ENTERPRISE'),
            'amount' => 9900,
            'features' => [
                'Everything in Premium',
                'Unlimited members',
                'SSO + audit logs',
                'Dedicated support',
            ],
        ],

    ],

];
