<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Tests\Feature;

use Illuminate\Support\Carbon;
use Mollie\Api\MollieApiClient;
use PHPUnit\Framework\Attributes\Test;
use Vanilo\Mollie\Configuration;
use Vanilo\Mollie\Messages\MolliePaymentRequest;
use Vanilo\Mollie\Tests\TestCase;

class PaymentRequestTest extends TestCase
{
    use CreatesTestGateway;
    use CreatesTestPayment;

    #[Test]
    public function a_payment_request_can_be_created_using_the_mollie_api()
    {
        $payment = $this->createTestPayment(178.99, 'EUR');

        /** @var MolliePaymentRequest $request */
        $request = $this->createTestGateway()->createPaymentRequest($payment);

        $this->assertStringStartsWith('ord_', $request->getRemoteId());

        $mollieClient = new MollieApiClient()->setApiKey(app(Configuration::class)->apiKey);
        $remoteOrder = $mollieClient->orders->get($request->getRemoteId());

        $this->assertEquals($payment->getAmount(), $remoteOrder->amount->value);
        $this->assertEquals($payment->getCurrency(), $remoteOrder->amount->currency);
        $this->assertStringEndsWith($payment->getPaymentId(), $remoteOrder->metadata->payment_id);
        $this->assertLessThan(15, Carbon::make($remoteOrder->createdAt)->diffInSeconds(absolute: true));

        $this->assertStringContainsString($remoteOrder->getCheckoutUrl(), $request->getHtmlSnippet());
    }
}