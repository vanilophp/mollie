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
    'return_url' => env('MOLLIE_RETURN_URL', ''),
    'webhook_url' => env('MOLLIE_WEBHOOK_URL', ''),
];
