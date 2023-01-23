<?php

declare(strict_types=1);

use Vanilo\Mollie\MolliePaymentGateway;

return [
    'gateway' => [
        'register' => true,
        'id' => MolliePaymentGateway::DEFAULT_ID
    ],
    'bind' => true,
    'xxx' => env('MOLLIE_Mollie'),
];
