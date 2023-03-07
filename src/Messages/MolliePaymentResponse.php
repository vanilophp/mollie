<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Messages;

use Konekt\Enum\Enum;
use Mollie\Api\Resources\Order;
use Vanilo\Mollie\Models\MollieOrderStatus;
use Vanilo\Payment\Contracts\PaymentResponse;
use Vanilo\Payment\Contracts\PaymentStatus;
use Vanilo\Payment\Models\PaymentStatusProxy;

/**
 * @see https://docs.mollie.com/orders/status-changes
 */
final class MolliePaymentResponse implements PaymentResponse
{
    private string $paymentId;

    private float $totalAmountOfOrder;

    private float $totalAmountCaptured;

    private float $totalAmountRefunded;

    private MollieOrderStatus $nativeStatus;

    private ?PaymentStatus $status = null;

    private ?string $transactionId;

    public static function createFromOrder(Order $order): self
    {
        $result = new self();

        $result->nativeStatus = new MollieOrderStatus($order->status);
        $result->transactionId = $order->id;
        $result->totalAmountOfOrder = (float) $order->amount->value;
        $result->totalAmountCaptured = (float) $order->amountCaptured?->value;
        $result->totalAmountRefunded = (float) $order->amountRefunded?->value;
        $result->paymentId = $order->metadata->payment_id;

        return $result;
    }

    public function wasSuccessful(): bool
    {
        return $this->getStatus()->isPending() || $this->getStatus()->isAuthorized() || $this->getStatus()->isPaid();
    }

    public function getMessage(): string
    {
        return $this->message ?? $this->nativeStatus->label();
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function getAmountPaid(): ?float
    {
        return match ($this->getNativeStatus()->value()) {
            MollieOrderStatus::STATUS_CREATED => 0,
            MollieOrderStatus::STATUS_PENDING => 0,
            MollieOrderStatus::STATUS_AUTHORIZED => $this->totalAmountOfOrder, // @todo Compare with previous status
            MollieOrderStatus::STATUS_CANCELED => -1 * $this->totalAmountOfOrder, //@todo Compare with existing order status and existing refunds
            MollieOrderStatus::STATUS_EXPIRED => 0,
            MollieOrderStatus::STATUS_PAID => $this->totalAmountCaptured, // @todo check previous status
            MollieOrderStatus::STATUS_SHIPPING => 0,
            MollieOrderStatus::STATUS_COMPLETED => 0,
            MollieOrderStatus::STATUS_REFUNDED => -1 * $this->totalAmountRefunded,
            default => 0,
        };
    }

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    public function getStatus(): PaymentStatus
    {
        if (null === $this->status) {
            /** @see https://docs.mollie.com/orders/status-changes */
            $this->status = match ($this->getNativeStatus()->value()) {
                MollieOrderStatus::STATUS_CREATED => PaymentStatusProxy::PENDING(),
                MollieOrderStatus::STATUS_PENDING => PaymentStatusProxy::ON_HOLD(),
                MollieOrderStatus::STATUS_AUTHORIZED => PaymentStatusProxy::AUTHORIZED(),
                MollieOrderStatus::STATUS_CANCELED => PaymentStatusProxy::CANCELLED(),
                MollieOrderStatus::STATUS_EXPIRED => PaymentStatusProxy::TIMEOUT(),
                MollieOrderStatus::STATUS_PAID => PaymentStatusProxy::PAID(),
                MollieOrderStatus::STATUS_SHIPPING => PaymentStatusProxy::PAID(),
                MollieOrderStatus::STATUS_COMPLETED => PaymentStatusProxy::PAID(),
                MollieOrderStatus::STATUS_REFUNDED => PaymentStatusProxy::REFUNDED(),
                default => PaymentStatusProxy::ON_HOLD(),// Shouldn't happen, but it worth checking
            };
        }

        return $this->status;
    }

    public function getNativeStatus(): Enum
    {
        return $this->nativeStatus;
    }
}
