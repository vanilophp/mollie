<?php

declare(strict_types=1);

namespace Vanilo\Mollie;

use Illuminate\Http\Request;
use Vanilo\Contracts\Address;
use Vanilo\Payment\Contracts\Payment;
use Vanilo\Payment\Contracts\PaymentGateway;
use Vanilo\Payment\Contracts\PaymentRequest;
use Vanilo\Payment\Contracts\PaymentResponse;

class MolliePaymentGateway implements PaymentGateway
{
    public const DEFAULT_ID = 'mollie';

    public static function getName(): string
    {
        return 'Mollie';
    }

    public function createPaymentRequest(Payment $payment, Address $shippingAddress = null, array $options = []): PaymentRequest
    {
        // @todo implement
    }

    public function processPaymentResponse(Request $request, array $options = []): PaymentResponse
    {
        // @todo implement
    }

    public function isOffline(): bool
    {
        return false;
    }
}
