<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Tests\Gateway;

use Vanilo\Mollie\Tests\TestCase;
use Vanilo\Payment\PaymentGateways;

class OmitRegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        PaymentGateways::reset();
        parent::setUp();
    }

    /** @test */
    public function the_gateway_registration_can_be_disabled()
    {
        $this->assertCount(1, PaymentGateways::ids());
    }

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        config(['vanilo.mollie.gateway.register' => false]);
    }
}
