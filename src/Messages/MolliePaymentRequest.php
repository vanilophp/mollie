<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Messages;

use Illuminate\Support\Facades\View;
use Mollie\Api\Resources\Order;
use Vanilo\Contracts\Payable;
use Vanilo\Mollie\Concerns\HasFullMollieConstructor;
use Vanilo\Mollie\Concerns\InteractsWithMollieApi;
use Vanilo\Payment\Contracts\Payment;
use Vanilo\Payment\Contracts\PaymentRequest;

class MolliePaymentRequest implements PaymentRequest
{
    use InteractsWithMollieApi;
    use HasFullMollieConstructor;

    private string $paymentId;

    private string $currency;

    private float $amount;

    private string $view = 'mollie::_request';

    private ?Order $molliePayment;

    public function create(Payment $payment): self
    {
        $billPayer = $payment->getPayable()->getBillpayer();

        $this->molliePayment = $this->mollie()->orders->create([
            'amount' => [
                'currency' => $payment->getCurrency(),
                'value' => $this->formatPrice($payment->getAmount()),
            ],
            'orderNumber' => $payment->getPayable()->getTitle(),
            'locale' => 'en_US',
            'billingAddress' => [
                'givenName' => $billPayer->getFirstName(),
                'familyName' => $billPayer->getLastName(),
                'email' => $billPayer->getEmail(),
                'phone' => $billPayer->getPhone(),
                'organizationName' => $billPayer->getCompanyName(),
                'streetAndNumber' => $billPayer->getBillingAddress()->getAddress(),
                'postalCode' => $billPayer->getBillingAddress()->getPostalCode(),
                'city' => $billPayer->getBillingAddress()->getCity(),
                'country' => $billPayer->getBillingAddress()->getCountryCode(),
            ],
            'lines' => $this->prepareOrderLines($payment),
            'redirectUrl' => $this->redirectUrl,
            'webhookUrl' => $this->webhookUrl,
            'metadata' => [
                'payment_id' => $payment->getPaymentId(),
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

    private function prepareOrderLines(Payment $payment): array
    {
        $payable = $payment->getPayable();
        $currency = $payment->getCurrency();

        $result = [];
        if (method_exists($payable, 'getItems')) {
            $result = $payable->getItems()->map(function ($item) use ($currency) {
                return [
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'sku' => $item->product->sku,
                    'unitPrice' => [
                        'currency' => $currency,
                        'value' => $this->formatPrice($item->product->price),
                    ],
                    'totalAmount' => [
                        'currency' => $currency,
                        'value' => $this->formatPrice($item->total),
                    ],
                    'vatRate' => 0,
                    'vatAmount' => [
                        "currency" => $currency,
                        "value" => "0.00",
                    ],
                ];
            })->toArray();
        }

        $result[] = $this->getAdjustments($payable, $currency);

        return [
            'name' => 'Orders total',
            'quantity' => 1,
            'sku' => 'N/A',
            'unitPrice' => [
                'currency' => $currency,
                'value' => $this->formatPrice($payable->getAmount()),
            ],
            'totalAmount' => [
                'currency' => $currency,
                'value' => $this->formatPrice($payable->getAmount()),
            ],
            'vatRate' => 0,
            'vatAmount' => [
                "currency" => $currency,
                "value" => "0.00",
            ],
        ];
    }

    private function formatPrice($price): string
    {
        return (string) number_format($price, 2, '.', '');
    }

    private function getAdjustments(Payable $payable, string $currency): array
    {
        $result = [];

        if (method_exists($payable, 'adjustments') &&
            class_exists('\Vanilo\Adjustments\Contracts\AdjustmentCollection') &&
            $adjustments = $payable->adjustments() instanceof \Vanilo\Adjustments\Contracts\AdjustmentCollection
        ) {
            foreach ($payable->adjustments as $adjustment) {
                if (!$adjustment->isIncluded() && 0.0 !== $adjustments->getAmount()) {
                    $result[] = [
                        'name' => $adjustment->getTitle ?: ($adjustment->getType()->label() . ' ' . $adjustment->isCredit() ? __('discount') : __('fee')),
                        'quantity' => 1,
                        'sku' => $adjustment->id ?? 'N/A',
                        'unitPrice' => [
                            'currency' => $currency,
                            'value' => $this->formatPrice($adjustment->getAmount()),
                        ],
                        'totalAmount' => [
                            'currency' => $currency,
                            'value' => $this->formatPrice($adjustment->getAmount()),
                        ],
                        'vatRate' => 0,
                        'vatAmount' => [
                            "currency" => $currency,
                            "value" => "0.00",
                        ],
                    ];
                }
            }
        }

        return $result;
    }
}
