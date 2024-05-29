<?php

declare(strict_types=1);

/**
 * Contains the OrderFactory class.
 *
 * @copyright   Copyright (c) 2023 Vanilo UG
 * @author      Attila Fulop
 * @license     MIT
 * @since       2023-03-06
 *
 */

namespace Vanilo\Mollie\Factories;

use Mollie\Api\Resources\Order;
use Propaganistas\LaravelPhone\PhoneNumber;
use Vanilo\Contracts\Payable;
use Vanilo\Mollie\Concerns\ConstructsApiClientFromConfiguration;
use Vanilo\Mollie\Concerns\FormatsPriceForApi;
use Vanilo\Mollie\Utils\LocaleResolver;
use Vanilo\Payment\Contracts\Payment;

final class OrderFactory
{
    use ConstructsApiClientFromConfiguration;
    use FormatsPriceForApi;

    public function createForPayment(Payment $payment, string $webhookUrl, string $redirectUrl, string $subtype = null): Order
    {
        $payable = $payment->getPayable();
        $billPayer = $payable->getBillpayer();
        $paymentSpecificParameters = $subtype ? ['method' => $subtype] : [];

        $payload = [
            'amount' => [
                'currency' => $payable->getCurrency(),
                'value' => $this->formatPrice($payable->getAmount()),
            ],
            'orderNumber' => $payable->getTitle(),
            'locale' => LocaleResolver::makeAnEducatedGuess($payable),
            'billingAddress' => [
                'givenName' => $billPayer->getFirstName(),
                'familyName' => $billPayer->getLastName(),
                'email' => $billPayer->getEmail(),
                'organizationName' => $billPayer->getCompanyName(),
                'streetAndNumber' => $billPayer->getBillingAddress()->getAddress(),
                'postalCode' => $billPayer->getBillingAddress()->getPostalCode(),
                'city' => $billPayer->getBillingAddress()->getCity(),
                'country' => $billPayer->getBillingAddress()->getCountryCode(),
            ],
            'lines' => $this->prepareOrderLines($payable),
            'redirectUrl' => $redirectUrl,
            'webhookUrl' => $webhookUrl,
            'payment' => $paymentSpecificParameters,
            'metadata' => [
                'payment_id' => $payment->getPaymentId(),
            ],
        ];

        if (!empty($billPayer->getPhone())) {
            $payload['billingAddress']['phone'] = (new PhoneNumber($billPayer->getPhone(), $billPayer->getBillingAddress()->getCountryCode()))->formatE164();
        }

        $order = $this->apiClient->orders->create($payload, ['embed' => 'payments']);

        return $order;
    }

    private function prepareOrderLines(Payable $payable): array
    {
        $currency = $payable->getCurrency();

        $result = [];
        if (method_exists($payable, 'getItems')) {
            $result = $payable->getItems()->map(function ($item) use ($currency) {
                return [
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'sku' => $item->product->sku,
                    'unitPrice' => [
                        'currency' => $currency,
                        'value' => $this->formatPrice($item->price),
                    ],
                    'totalAmount' => [
                        'currency' => $currency,
                        'value' => $this->formatPrice($item->total()),
                    ],
                    'vatRate' => 0,
                    'vatAmount' => [
                        "currency" => $currency,
                        "value" => "0.00",
                    ],
                ];
            })->toArray();
        }

        if (!empty($adjustments = $this->getAdjustments($payable))) {
            $result = array_merge($result, $adjustments);
        }

        if (empty($result)) {
            $result[] = $this->makeASingleOrderItemFromTheEntirePayable($payable);
        }

        return $result;
    }

    private function getAdjustments(Payable $payable): array
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
                        'type' => $adjustment->getType()->isShipping() ? 'shipping_fee' : ($adjustment->isCredit() ? 'discount' : 'surcharge'),
                        'quantity' => 1,
                        'sku' => (string) $adjustment->id ?? 'N/A',
                        'unitPrice' => [
                            'currency' => $payable->getCurrency(),
                            'value' => $this->formatPrice($adjustment->getAmount()),
                        ],
                        'totalAmount' => [
                            'currency' => $payable->getCurrency(),
                            'value' => $this->formatPrice($adjustment->getAmount()),
                        ],
                        'vatRate' => 0,
                        'vatAmount' => [
                            "currency" => $payable->getCurrency(),
                            "value" => "0.00",
                        ],
                    ];
                }
            }
        }

        return $result;
    }

    private function makeASingleOrderItemFromTheEntirePayable(Payable $payable): array
    {
        return [
            'name' => 'Orders total',
            'quantity' => 1,
            'sku' => 'N/A',
            'unitPrice' => [
                'currency' => $payable->getCurrency(),
                'value' => $this->formatPrice($payable->getAmount()),
            ],
            'totalAmount' => [
                'currency' => $payable->getCurrency(),
                'value' => $this->formatPrice($payable->getAmount()),
            ],
            'vatRate' => 0,
            'vatAmount' => [
                "currency" => $payable->getCurrency(),
                "value" => "0.00",
            ],
        ];
    }
}
