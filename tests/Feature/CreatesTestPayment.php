<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Tests\Feature;

use Vanilo\Mollie\Tests\Dummies\Order;
use Vanilo\Payment\Factories\PaymentFactory;
use Vanilo\Payment\Models\Payment;
use Vanilo\Payment\Models\PaymentMethod;

trait CreatesTestPayment
{
    private function createTestPayment(float $amount, string $currency = 'EUR'): Payment
    {
        $paymentMethod = PaymentMethod::create([
            'name' => 'Mollie',
            'gateway' => 'mollie',
            'is_enabled' => true,
        ]);

        $order = Order::create([
            'amount' => $amount,
            'currency' => $currency,
        ]);

        $payment = PaymentFactory::createFromPayable($order, $paymentMethod);

        return $payment;
    }

}