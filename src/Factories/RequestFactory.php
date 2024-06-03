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

use Vanilo\Mollie\Concerns\FormatsPriceForApi;
use Vanilo\Mollie\Concerns\GetsCreatedWithConfiguration;
use Vanilo\Mollie\Messages\MolliePaymentRequest;
use Vanilo\Payment\Contracts\Payment;
use Vanilo\Payment\Support\ReplacesPaymentUrlParameters;

final class RequestFactory
{
    use ReplacesPaymentUrlParameters;
    use GetsCreatedWithConfiguration;
    use FormatsPriceForApi;

    private ?OrderFactory $_orderFactory = null;

    public function create(Payment $payment, ?string $subtype, ?string $redirectUrl, ?string $webhookUrl, ?string $view): MolliePaymentRequest
    {
        $mollieOrder = $this->orderFactory()
            ->createForPayment(
                $payment,
                $this->url($payment, 'webhook', $webhookUrl),
                $this->url($payment, 'redirect', $redirectUrl),
            );

        $paymentRequest = new MolliePaymentRequest($mollieOrder);

        if (null !== $view) {
            $paymentRequest->setView($view);
        }

        return $paymentRequest;
    }

    private function orderFactory(): OrderFactory
    {
        if (null === $this->_orderFactory) {
            $this->_orderFactory = new OrderFactory($this->configuration);
        }

        return $this->_orderFactory;
    }

    private function url(Payment $payment, string $which, ?string $forceUrl = null): ?string
    {
        $prop = $which . 'Url';
        $url = $forceUrl ?? $this->configuration->{$prop};

        if (null !== $url) {
            $url = $this->replaceUrlParameters($url, $payment);
        }

        return $url;
    }
}
