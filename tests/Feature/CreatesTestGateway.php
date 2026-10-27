<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Tests\Feature;

use Vanilo\Mollie\MolliePaymentGateway;

trait CreatesTestGateway
{
    private function createTestGateway(): MolliePaymentGateway
    {
        config()->set('vanilo.mollie.webhook_url', 'https://vanilo.io/test/w/{paymentId}');
        config()->set('vanilo.mollie.return_url', 'https://vanilo.io/test/r/{paymentId}');

        return $this->app->make(MolliePaymentGateway::class);
    }
}