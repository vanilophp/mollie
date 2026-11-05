<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Messages;

use DateTimeImmutable;
use Vanilo\Snapshot\Contracts\SnapshotPayload;
use Vanilo\Snapshot\SnapshotMetadata;

class MolliePaymentRequestSnapshotPayload implements SnapshotPayload
{
    public const int VERSION = 2;

    public const string DEFAULT_ID = 'mollie_payment_request';

    public function __construct(
        private readonly array $data,
        private readonly DateTimeImmutable $takenAt,
        private readonly DateTimeImmutable $validUntil,
    ) {
    }

    public static function fromArray(array $data, SnapshotMetadata $meta): static
    {
        if ($meta->version !== self::VERSION) {
            throw new \InvalidArgumentException(sprintf('Invalid snapshot version. Expected %d, got %s', self::VERSION, $meta->version));
        }

        return new static($data, $meta->takenAt, $meta->validUntil);
    }


    public function version(): int
    {
        return self::VERSION;
    }

    public function takenAt(): DateTimeImmutable
    {
        return $this->takenAt;
    }

    public function validUntil(): DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function getOrder(): \stdClass
    {
        return $this->data['order'];
    }

    public function getView(): ?string
    {
        return $this->data['view'] ?? null;
    }
}