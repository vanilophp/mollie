<?php

declare(strict_types=1);

/**
 * Contains the RequestFactory class.
 *
 * @copyright   Copyright (c) 2023 Vanilo UG
 * @author      Attila Fulop
 * @license     MIT
 * @since       2023-03-06
 *
 */

namespace Vanilo\Mollie\Factories;

use Vanilo\Mollie\Concerns\HasApiKeyConstructor;
use Vanilo\Mollie\Messages\MolliePaymentRequest;
use Vanilo\Payment\Contracts\Payment;
use Vanilo\Payment\Support\ReplacesPaymentUrlParameters;

final class RequestFactory
{
    use ReplacesPaymentUrlParameters;
    use HasApiKeyConstructor;

    public function create(Payment $payment, ?string $redirectUrl, ?string $webhookUrl, ?string $view): MolliePaymentRequest
    {
        $paymentRequest = new MolliePaymentRequest(
            $this->apiKey,
            $this->url($payment, 'webhook', $webhookUrl),
            $this->url($payment, 'redirect', $redirectUrl),
        );

        if (null !== $view) {
            $paymentRequest->setView($view);
        }

        return $paymentRequest->create($payment);
    }

    private function url(Payment $payment, string $which, ?string $forceUrl): ?string
    {
        $prop = $which . 'Url';
        $url = $forceUrl ?? $this->{$prop};

        if (null !== $url) {
            $url = $this->replaceUrlParameters($url, $payment);
        }

        return $url;
    }
}
