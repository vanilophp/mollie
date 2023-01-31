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
        $payment = $this->apiClient->payments->get($remoteId);

        $this->nativeStatus = new MollieStatus($payment->status);
        $this->transactionId = $payment->id;
        $this->amountPaid = (float) $payment->amount->value;
        $this->paymentId = $payment->metadata->payment_id;

        if ($payment->isFailed()) {
            $this->message = $payment->details->failureMessage;
        }

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
                case MollieStatus::STATUS_OPEN:
                case MollieStatus::STATUS_PENDING:
                    $this->status = PaymentStatusProxy::PENDING();
                    break;
                case MollieStatus::STATUS_AUTHORIZED:
                    $this->status = PaymentStatusProxy::AUTHORIZED();
                    break;
                case MollieStatus::STATUS_CANCELED:
                case MollieStatus::STATUS_EXPIRED:
                case MollieStatus::STATUS_FAILED:
                    $this->status = PaymentStatusProxy::DECLINED();
                    break;
                case MollieStatus::STATUS_PAID:
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
