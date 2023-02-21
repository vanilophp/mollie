<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Messages;

use Konekt\Enum\Enum;
use Vanilo\Mollie\Concerns\HasMollieInteraction;
use Vanilo\Mollie\Models\MollieStatus;
use Vanilo\Payment\Contracts\PaymentResponse;
use Vanilo\Payment\Contracts\PaymentStatus;
use Vanilo\Payment\Models\PaymentStatusProxy;

class MolliePaymentResponse implements PaymentResponse
{
    use HasMollieInteraction;

    private string $paymentId;

    private ?float $amountPaid;

    private MollieStatus $nativeStatus;

    private ?PaymentStatus $status = null;

    private string $message;

    private ?string $transactionId;

    public function process(string $remoteId): self
    {
        $order = $this->apiClient->orders->get($remoteId);

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
            switch ($this->getNativeStatus()->value()) {
                case MollieStatus::STATUS_CREATED:
                case MollieStatus::STATUS_PENDING:
                    $this->status = PaymentStatusProxy::PENDING();
                    break;
                case MollieStatus::STATUS_AUTHORIZED:
                    $this->status = PaymentStatusProxy::AUTHORIZED();
                    break;
                case MollieStatus::STATUS_CANCELED:
                case MollieStatus::STATUS_EXPIRED:
                    $this->status = PaymentStatusProxy::DECLINED();
                    break;
                case MollieStatus::STATUS_PAID:
                case MollieStatus::STATUS_COMPLETED:
                    $this->status = PaymentStatusProxy::PAID();
                    break;
                default:
                    $this->status = PaymentStatusProxy::DECLINED();
            }
        }

        return $this->status;
    }

    public function getNativeStatus(): Enum
    {
        return $this->nativeStatus;
    }
}
