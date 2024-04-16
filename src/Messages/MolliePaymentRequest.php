<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Messages;

use Illuminate\Support\Facades\View;
use Mollie\Api\Resources\Payment;
use Vanilo\Payment\Contracts\PaymentRequest;

class MolliePaymentRequest implements PaymentRequest
{
    private string $view = 'mollie::_request';

    public function __construct(
        private Payment $molliePayment
    ) {
    }

    public function getHtmlSnippet(array $options = []): ?string
    {
        return View::make(
            $this->view,
            [
                'url' => $this->molliePayment->getCheckoutUrl(),
                'autoRedirect' => $options['autoRedirect'] ?? false,
            ]
        )->render();
    }

    public function willRedirect(): bool
    {
        return true;
    }

    public function setView(string $view): self
    {
        $this->view = $view;

        return $this;
    }

    public function getRemoteId(): ?string
    {
        return $this->molliePayment->id;
    }
}
