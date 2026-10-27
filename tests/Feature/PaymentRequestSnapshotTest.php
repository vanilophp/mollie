<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Tests\Feature;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Vanilo\Mollie\Messages\MolliePaymentRequest;
use Vanilo\Mollie\Messages\MolliePaymentRequestSnapshot;
use Vanilo\Mollie\Tests\TestCase;

class PaymentRequestSnapshotTest extends TestCase
{
    use CreatesTestPayment;
    use CreatesTestGateway;

    #[Test]
    public function a_snapshot_can_be_created_and_restored(): void
    {
        $payment = $this->createTestPayment(130, 'EUR');

        /** @var MolliePaymentRequest $request */
        $request = $this->createTestGateway()->createPaymentRequest($payment);

        $snapshot = $request->toSnapshot();
        $this->assertInstanceOf(MolliePaymentRequestSnapshot::class, $snapshot);
        $this->assertStringStartsWith('ord_', data_get($snapshot->data(), 'order.id'));
        $this->assertEquals($request->getRemoteId(), data_get($snapshot->data(), 'order.id'));
        $this->assertEquals($payment->getAmount(), (float) data_get($snapshot->data(), 'order.amount.value'));
        $this->assertEquals($payment->getCurrency(), data_get($snapshot->data(), 'order.amount.currency'));
        $this->assertEquals(
            Carbon::make(data_get($snapshot->data(), 'order.createdAt'))->getTimestamp(),
            $snapshot->takenAt()->getTimestamp(),
        );
        $this->assertEquals(
            Carbon::make(data_get($snapshot->data(), 'order.expiresAt'))->getTimestamp(),
            $snapshot->validUntil()->getTimestamp(),
        );

        $restoredRequest = MolliePaymentRequest::fromSnapshot($snapshot);
        $this->assertInstanceOf(MolliePaymentRequest::class, $restoredRequest);

        $reflection = new \ReflectionClass($restoredRequest);
        $property1 = $reflection->getProperty('mollieOrder');
        $property1->setAccessible(true);
        /** @var \Mollie\Api\Resources\Order $restoredMollieOrder */
        $restoredMollieOrder = $property1->getValue($restoredRequest);

        $property2 = $reflection->getProperty('view');
        $property2->setAccessible(true);
        $view = $property2->getValue($restoredRequest);

        $this->assertEquals(data_get($snapshot->data(), 'view'), $view);

        $this->assertEquals(data_get($snapshot->data(), 'order.id'), $restoredMollieOrder->id);
        $this->assertStringContainsString($restoredMollieOrder->getCheckoutUrl(), $restoredRequest->getHtmlSnippet());
    }
}