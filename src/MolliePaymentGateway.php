<?php

declare(strict_types=1);

namespace Vanilo\Mollie;

use Illuminate\Http\Request;
use Vanilo\Contracts\Address;
use Vanilo\Mollie\Concerns\HasFullMollieConstructor;
use Vanilo\Mollie\Factories\RequestFactory;
use Vanilo\Mollie\Messages\MolliePaymentResponse;
use Vanilo\Payment\Contracts\Payment;
use Vanilo\Payment\Contracts\PaymentGateway;
use Vanilo\Payment\Contracts\PaymentRequest;
use Vanilo\Payment\Contracts\PaymentResponse;

class MolliePaymentGateway implements PaymentGateway
{
    use HasFullMollieConstructor;

    public const DEFAULT_ID = 'mollie';

    private ?RequestFactory $requestFactory = null;

    public static function getName(): string
    {
        return 'Mollie';
    }

    public function createPaymentRequest(Payment $payment, Address $shippingAddress = null, array $options = []): PaymentRequest
    {
        return $this->requestFactory()->create(
            $payment,
            $options['redirect_url'] ?? $this->redirectUrl,
            $options['webhook_url'] ?? $this->webhookUrl,
            $options['view'] ?? null,
        );
    }

    public function processPaymentResponse(Request|string $request, array $options = []): PaymentResponse
    {
        return (new MolliePaymentResponse($this->apiKey))->process($request);
    }

    public function isOffline(): bool
    {
        return false;
    }

    private function requestFactory(): RequestFactory
    {
        if (null === $this->requestFactory) {
            $this->requestFactory = new RequestFactory($this->apiKey);
        }

        return $this->requestFactory;
    }
}
