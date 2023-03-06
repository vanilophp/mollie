<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Messages;

use Konekt\Enum\Enum;
use Vanilo\Mollie\Concerns\HasApiKeyConstructor;
use Vanilo\Mollie\Concerns\InteractsWithMollieApi;
use Vanilo\Mollie\Models\MollieStatus;
use Vanilo\Payment\Contracts\PaymentResponse;
use Vanilo\Payment\Contracts\PaymentStatus;
use Vanilo\Payment\Models\PaymentStatusProxy;

class MolliePaymentResponse implements PaymentResponse
{
    use InteractsWithMollieApi;
    use HasApiKeyConstructor;

    private string $paymentId;

    private ?float $amountPaid;

    private MollieStatus $nativeStatus;

    private ?PaymentStatus $status = null;

    private string $message;

    private ?string $transactionId;

    public function process(string $remoteId): self
    {
        $order = $this->mollie()->orders->get($remoteId);

        $this->nativeStatus = new MollieStatus($order->status);
        $this->transactionId = $order->id;
        $this->amountPaid = (float) $order->amount->value;
        $this->paymentId = $order->metadata->payment_id;

        return $this;
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
        // Make sure to return a negative amount if the transaction
        // the response represents was a refund, partial refund cancellation or similar etc
        return $this->amountPaid;
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
                MollieStatus::STATUS_CREATED => PaymentStatusProxy::PENDING(),
                MollieStatus::STATUS_PENDING => PaymentStatusProxy::ON_HOLD(),
                MollieStatus::STATUS_AUTHORIZED => PaymentStatusProxy::AUTHORIZED(),
                MollieStatus::STATUS_CANCELED => PaymentStatusProxy::CANCELLED(),
                MollieStatus::STATUS_EXPIRED => PaymentStatusProxy::TIMEOUT(),
                MollieStatus::STATUS_PAID => PaymentStatusProxy::PAID(),
                MollieStatus::STATUS_SHIPPING => PaymentStatusProxy::PAID(),
                MollieStatus::STATUS_COMPLETED => PaymentStatusProxy::PAID(),
                MollieStatus::STATUS_REFUNDED => PaymentStatusProxy::REFUNDED(),
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
