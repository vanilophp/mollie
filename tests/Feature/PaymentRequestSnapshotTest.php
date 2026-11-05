<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Tests\Feature;

use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Vanilo\Mollie\Messages\MolliePaymentRequest;
use Vanilo\Mollie\Messages\MolliePaymentRequestSnapshotPayload;
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

        $snapshot = $request->toSnapshotPayload();
        $this->assertInstanceOf(MolliePaymentRequestSnapshotPayload::class, $snapshot);
        $order = $snapshot->getOrder();
        $this->assertStringStartsWith('ord_', $order->id);
        $this->assertEquals($request->getRemoteId(), $order->id);
        $this->assertEquals($payment->getAmount(), (float) $order->amount->value);
        $this->assertEquals($payment->getCurrency(), $order->amount->currency);
        $this->assertEquals(
            Carbon::make($order->createdAt)->getTimestamp(),
            $snapshot->takenAt()->getTimestamp(),
        );
        $this->assertEquals(
            Carbon::make($order->expiresAt)->getTimestamp(),
            $snapshot->validUntil()->getTimestamp(),
        );

        $restoredRequest = MolliePaymentRequest::fromSnapshotPayload($snapshot);
        $this->assertInstanceOf(MolliePaymentRequest::class, $restoredRequest);

        $reflection = new \ReflectionClass($restoredRequest);
        $property1 = $reflection->getProperty('mollieOrder');
        $property1->setAccessible(true);
        /** @var \Mollie\Api\Resources\Order $restoredMollieOrder */
        $restoredMollieOrder = $property1->getValue($restoredRequest);

        $property2 = $reflection->getProperty('view');
        $property2->setAccessible(true);
        $view = $property2->getValue($restoredRequest);

        $this->assertEquals($snapshot->getView(), $view);

        $this->assertEquals($order->id, $restoredMollieOrder->id);
        $this->assertStringContainsString($restoredMollieOrder->getCheckoutUrl(), $restoredRequest->getHtmlSnippet());
    }
}
