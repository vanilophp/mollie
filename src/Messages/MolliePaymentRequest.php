<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Messages;

use Illuminate\Support\Facades\View;
use Vanilo\Mollie\Concerns\HasMollieInteraction;
use Vanilo\Payment\Contracts\PaymentRequest;
use Mollie\Api\Resources\Payment;

class MolliePaymentRequest implements PaymentRequest
{
    use HasMollieInteraction;

    private string $paymentId;

    private string $currency;

    private float $amount;

    private string $redirectUrl;

    private string $webhookUrl;

    private string $view = 'mollie::_request';

    private ?Payment $molliePayment;

    public function create(): self
    {
        $this->molliePayment = $this->apiClient->payments->create([
            "amount" => [
                "currency" => $this->currency,
                "value" => (string)number_format($this->amount, 2, '.', ''),
            ],
            "description" => "Payment for $this->paymentId",
            "redirectUrl" => $this->redirectUrl,
            "webhookUrl" => $this->webhookUrl,
            "metadata" => [
                'payment_id' => $this->paymentId,
            ],
        ]);

        return $this;
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

    public function getRemoteId(): string
    {
        return $this->molliePayment->id;
    }

    public function willRedirect(): bool
    {
        return true;
    }

    public function setPaymentId(string $paymentId): self
    {
        $this->paymentId = $paymentId;

        return $this;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function setRedirectUrl(string $redirectUrl): self
    {
        $this->redirectUrl = $redirectUrl;

        return $this;
    }

    public function setWebhookUrl(string $webhookUrl): self
    {
        $this->webhookUrl = $webhookUrl;

        return $this;
    }


    public function setView(string $view): self
    {
        $this->view = $view;

        return $this;
    }
}
