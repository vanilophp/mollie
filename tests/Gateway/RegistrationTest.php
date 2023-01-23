<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Tests\Gateway;

use Vanilo\Mollie\MolliePaymentGateway;
use Vanilo\Mollie\Tests\TestCase;
use Vanilo\Payment\Contracts\PaymentGateway;
use Vanilo\Payment\PaymentGateways;

class RegistrationTest extends TestCase
{
    /** @test */
    public function the_gateway_is_registered_out_of_the_box_with_defaults()
    {
        $this->assertCount(2, PaymentGateways::ids());
        $this->assertContains(MolliePaymentGateway::DEFAULT_ID, PaymentGateways::ids());
    }

    /** @test */
    public function the_gateway_can_be_instantiated()
    {
        $gateway = PaymentGateways::make('mollie');

        $this->assertInstanceOf(PaymentGateway::class, $gateway);
        $this->assertInstanceOf(MolliePaymentGateway::class, $gateway);
    }
}
