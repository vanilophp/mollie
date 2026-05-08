<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Tests;

use Mollie\Api\Resources\Order;
use PHPUnit\Framework\Attributes\Test;
use Vanilo\Mollie\Configuration;
use Vanilo\Mollie\Factories\OrderFactory;
use Vanilo\Mollie\Tests\Dummies\Order as DummyOrder;
use Vanilo\Mollie\Tests\Dummies\Product;
use Vanilo\Mollie\Tests\Fakes\FakeMollieHttpAdapter;
use Vanilo\Payment\Factories\PaymentFactory;
use Vanilo\Payment\Models\PaymentMethod;

class OrderFactoryTest extends TestCase
{
    #[Test]
    public function it_can_create_an_order()
    {
        $fakeAdapter = new FakeMollieHttpAdapter();
        $factory = (new OrderFactory(new Configuration(FakeMollieHttpAdapter::FAKE_API_KEY, 'qwe', 'xzy')))->fake($fakeAdapter);
        $product = Product::create(['name' => 'Something', 'sku' => 'something', 'price' => 100]);

        $sourceOrder = DummyOrder::create(['amount' => 300, 'currency' => 'EUR']);

        $sourceOrder->addItem($product);
        $paymentMethod = PaymentMethod::create(['name' => 'Mollie', 'gateway' => 'mollie']);
        $payment = PaymentFactory::createFromPayable($sourceOrder, $paymentMethod);

        $order = $factory->createForPayment($payment, 'qwe', 'xyz');

        $this->assertInstanceOf(Order::class, $order);
        dd(json_decode($fakeAdapter->requests[0]['body']));
    }
}
