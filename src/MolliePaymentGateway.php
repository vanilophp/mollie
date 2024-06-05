<?php

declare(strict_types=1);

namespace Vanilo\Mollie;

use Illuminate\Http\Request;
use Vanilo\Contracts\Address;
use Vanilo\Mollie\Concerns\GetsCreatedWithConfiguration;
use Vanilo\Mollie\Factories\RequestFactory;
use Vanilo\Mollie\Factories\ResponseFactory;
use Vanilo\Mollie\Transaction\Handler;
use Vanilo\Payment\Contracts\Payment;
use Vanilo\Payment\Contracts\PaymentGateway;
use Vanilo\Payment\Contracts\PaymentRequest;
use Vanilo\Payment\Contracts\PaymentResponse;
use Vanilo\Payment\Contracts\TransactionHandler;

class MolliePaymentGateway implements PaymentGateway
{
    use GetsCreatedWithConfiguration;

    public const DEFAULT_ID = 'mollie';

    private static ?string $svg = null;

    private ?RequestFactory $requestFactory = null;

    private ?ResponseFactory $responseFactory = null;

    private ?Handler $transactionHandler = null;

    public static function getName(): string
    {
        return 'Mollie';
    }

    public static function svgIcon(): string
    {
        return self::$svg ??= file_get_contents(__DIR__ . '/resources/logo.svg');
    }

    public function createPaymentRequest(Payment $payment, Address $shippingAddress = null, array $options = []): PaymentRequest
    {
        return $this->requestFactory()->create(
            $payment,
            $options['subtype'] ?? null,
            $options['redirect_url'] ?? null,
            $options['webhook_url'] ?? null,
            $options['view'] ?? null,
        );
    }

    public function processPaymentResponse(Request $request, array $options = []): PaymentResponse
    {
        /**
         * @see https://docs.mollie.com/overview/webhooks
         * @see https://docs.mollie.com/orders/status-changes
         * This method has to receive the request coming from a Mollie Order Webhook call
         * Mollie calls the webhooks for the following order states:
         * - paid
         * - authorized
         * - completed
         * - canceled
         * - expired
         */
        return $this->responseFactory()->create($request->input('id'));
    }

    public function transactionHandler(): ?TransactionHandler
    {
        if (null === $this->transactionHandler) {
            $this->transactionHandler = new Handler($this->configuration);
        }

        return $this->transactionHandler;
    }

    public function isOffline(): bool
    {
        return false;
    }

    private function requestFactory(): RequestFactory
    {
        if (null === $this->requestFactory) {
            $this->requestFactory = new RequestFactory($this->configuration);
        }

        return $this->requestFactory;
    }

    private function responseFactory(): ResponseFactory
    {
        if (null === $this->responseFactory) {
            $this->responseFactory = new ResponseFactory($this->configuration);
        }

        return $this->responseFactory;
    }
}
