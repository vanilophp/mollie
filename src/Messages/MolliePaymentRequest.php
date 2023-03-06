<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Messages;

use Illuminate\Support\Facades\App;
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

    private const SUPPORTED_LOCALES = [
        'en_US', 'en_GB', 'nl_NL', 'nl_BE', 'fr_FR', 'fr_BE', 'de_DE', 'de_AT', 'de_CH', 'es_ES', 'ca_ES',
        'pt_PT', 'it_IT', 'nb_NO', 'sv_SE', 'fi_FI', 'da_DK', 'is_IS', 'hu_HU', 'pl_PL', 'lv_LV', 'lt_LT',
    ];

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
            'locale' => $this->calculateLocale($payment),
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

        if (!empty($adjustments = $this->getAdjustments($payable, $currency))) {
            $result = array_merge($result, $adjustments);
        }

        if (empty($result)) {
            $result[] = [
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

        return $result;
    }

    private function formatPrice($price): string
    {
        return (string) number_format($price, 2, '.', '');
    }

    private function getAdjustments(Payable $payable, string $currency): array
    {
        $result = [];

        if (method_exists($payable, 'adjustments') &&
            interface_exists('\Vanilo\Adjustments\Contracts\AdjustmentCollection') &&
            ($adjustments = $payable->adjustments()) instanceof \Vanilo\Adjustments\Contracts\AdjustmentCollection
        ) {
            foreach ($adjustments as $adjustment) {
                if (!$adjustment->isIncluded() && 0.0 !== $adjustment->getAmount()) {
                    $result[] = [
                        'name' => $adjustment->getTitle() ?: ($adjustment->getType()->label() . ' ' . $adjustment->isCredit() ? __('discount') : __('fee')),
                        'quantity' => 1,
                        'sku' => (string) $adjustment->id ?? 'N/A',
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

    private function calculateLocale(Payment $payment): string
    {
        $locale = $this->guessPaymentLocale($payment);
        if (!is_null($locale)) {
            if ($this->isSupportedLocale($locale) || $this->looksLikeALocale($locale)) {
                return $locale;
            }
        }

        $locale = $this->guessAppLocale($payment);
        if (!is_null($locale)) {
            if ($this->isSupportedLocale($locale) || $this->looksLikeALocale($locale)) {
                return $locale;
            }
        }

        return 'en_US';
    }

    private function guessPaymentLocale(Payment $payment): ?string
    {
        $payable = $payment->getPayable();
        if (!method_exists($payable, 'getLanguage')) {
            return null;
        }

        if (!is_string($lang = $payable->getLanguage())) {
            return null;
        }

        return match (strlen($lang)) {
            2 => $lang . '_' . $payable->getBillpayer()->getBillingAddress()->getCountryCode(),
            5 => $lang,
            default => null,
        };
    }

    private function guessAppLocale(Payment $payment): ?string
    {
        if (!is_string($lang = App::currentLocale())) {
            return null;
        }

        return match (strlen($lang)) {
            2 => $lang . '_' . $payment->getPayable()->getBillpayer()->getBillingAddress()->getCountryCode(),
            5 => $lang,
            default => null,
        };
    }

    private function isSupportedLocale(string $locale): bool
    {
        return in_array($locale, self::SUPPORTED_LOCALES);
    }

    private function looksLikeALocale(string $locale): bool
    {
        return
            5 === strlen($locale) &&
            preg_match('/[a-zA-Z][a-zA-Z]_[a-zA-Z][a-zA-Z]/', $locale);
    }
}
