<?php

declare(strict_types=1);

namespace Vanilo\Mollie;

use Illuminate\Http\Request;
use Vanilo\Contracts\Address;
use Vanilo\Mollie\Messages\MolliePaymentRequest;
use Vanilo\Mollie\Messages\MolliePaymentResponse;
use Vanilo\Payment\Contracts\Payment;
use Vanilo\Payment\Contracts\PaymentGateway;
use Vanilo\Payment\Contracts\PaymentRequest;
use Vanilo\Payment\Contracts\PaymentResponse;

class MolliePaymentGateway implements PaymentGateway
{
    public const DEFAULT_ID = 'mollie';

    public function __construct(private string $apiKey)
    {
    }

    public static function getName(): string
    {
        return 'Mollie';
    }

    public function createPaymentRequest(Payment $payment, Address $shippingAddress = null, array $options = []): PaymentRequest
    {
        return (new MolliePaymentRequest($this->apiKey))
            ->setPaymentId($payment->getPaymentId())
            ->setAmount($payment->getAmount())
            ->setCurrency($payment->getCurrency())
            ->setWebhookUrl($options['webhookUrl'])
            ->setRedirectUrl($options['redirectUrl'])
            ->create();
    }

    public function processPaymentResponse(Request|string $request, array $options = []): PaymentResponse
    {
        return (new MolliePaymentResponse($this->apiKey))->process($request);
    }

    public function isOffline(): bool
    {
        return false;
    }
}
