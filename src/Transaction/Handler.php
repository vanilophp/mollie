<?php

declare(strict_types=1);

/**
 * Contains the Handler class.
 *
 * @copyright   Copyright (c) 2024 Vanilo UG
 * @author      Attila Fulop
 * @license     MIT
 * @since       2024-06-04
 *
 */

namespace Vanilo\Mollie\Transaction;

use Mollie\Api\Resources\Order;
use Vanilo\Mollie\Concerns\ConstructsApiClientFromConfiguration;
use Vanilo\Mollie\Messages\MolliePaymentRequest;
use Vanilo\Payment\Contracts\Payment;
use Vanilo\Payment\Contracts\PaymentRequest;
use Vanilo\Payment\Contracts\Transaction;
use Vanilo\Payment\Contracts\TransactionHandler;
use Vanilo\Payment\Contracts\TransactionNotCreated;
use Vanilo\Payment\Responses\NoTransaction;

class Handler implements TransactionHandler
{
    use ConstructsApiClientFromConfiguration;

    /** @var Order[] */
    private array $orders = [];

    public function supportsRefunds(): bool
    {
        return true;
    }

    public function supportsRetry(): bool
    {
        return true;
    }

    public function allowsRefund(Payment $payment): bool
    {
        if (null === $order = $this->getOrder($payment)) {
            return false;
        }

        if (!($order->isPaid() || $order->isAuthorized() || $order->isCompleted())) {
            return false;
        }

        return (float) $order->amountRefunded?->value < (float) $order->amount?->value;
    }

    public function issueRefund(Payment $payment, float $amount, array $options = []): Transaction|TransactionNotCreated
    {
        return NoTransaction::create($payment, 'Feature not implemented');
    }

    public function canBeRetried(Payment $payment): bool
    {
        $order = $this->getOrder($payment);

        return $order->isCreated() || $order->isPending();
    }

    public function getRetryRequest(Payment $payment, array $options = []): PaymentRequest|TransactionNotCreated
    {
        if (!$this->canBeRetried($payment)) {
            return NoTransaction::create($payment, __('The payment is not in a state that allows retrying'));
        }

        return new MolliePaymentRequest($this->getOrder($payment));
    }

    private function getOrder(Payment $payment): ?Order
    {
        $id = $payment->getPaymentId();
        if (!isset($this->orders[$id])) {
            try {
                $this->orders[$id] = $this->apiClient->orders->get($payment->getRemoteId());
            } catch (\Exception $e) {
                $this->orders[$id] = null;
            }
        }

        return $this->orders[$id];
    }
}
