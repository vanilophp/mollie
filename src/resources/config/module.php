<?php

declare(strict_types=1);

use Vanilo\Mollie\MolliePaymentGateway;

return [
    'gateway' => [
        'register' => true,
        'id' => MolliePaymentGateway::DEFAULT_ID
    ],
    'bind' => true,
    'api_key' => env('MOLLIE_API_KEY'),
    'redirect_url' => env('MOLLIE_REDIRECT_URL', ''),
    'webhook_url' => env('MOLLIE_CONFIRM_URL', ''),
];
