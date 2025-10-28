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

use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Order;
use Propaganistas\LaravelPhone\PhoneNumber;
use Vanilo\Adjustments\Contracts\AdjustmentCollection;
use Vanilo\Contracts\Payable;
use Vanilo\Mollie\Concerns\ConstructsApiClientFromConfiguration;
use Vanilo\Mollie\Concerns\FormatsPriceForApi;
use Vanilo\Mollie\Tests\Fakes\FakeMollieHttpAdapter;
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

        return $this->apiClient->orders->create($payload, ['embed' => 'payments']);
    }

    public function fake(?FakeMollieHttpAdapter $adapter = null): self
    {
        $this->apiClient = new MollieApiClient($adapter ?? new FakeMollieHttpAdapter());
        $this->apiClient->setApiKey($this->configuration->apiKey);

        return $this;
    }

    private function prepareOrderLines(Payable $payable): array
    {
        $currency = $payable->getCurrency();

        $result = [];
        if ($payable->hasItems() || dd($payable)) {
            $result = $payable->getItems()->map(function ($item) use ($currency) {
                $discount = match (method_exists($item, 'adjustments')) {
                    true => $item->adjustments()->byType(\Vanilo\Adjustments\Models\AdjustmentType::create('promotion'))->total(),
                    default => 0,
                };
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
                    'discountAmount' => [
                        'currency' => $currency,
                        'value' => $this->formatPrice($discount),
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

        if ($adjustments = $this->getAdjustmentsOrFalse($payable)) {
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

    private function getAdjustmentsOrFalse(object $subject): false|AdjustmentCollection
    {
        if (!method_exists($subject, 'adjustments') || !interface_exists(AdjustmentCollection::class)) {
            return false;
        }

        $adjustments = $subject->adjustments();

        return $adjustments instanceof AdjustmentCollection ? $adjustments : false;
    }
}
