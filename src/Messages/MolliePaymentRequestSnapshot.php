<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Messages;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Vanilo\Contracts\Snapshot;

class MolliePaymentRequestSnapshot implements Snapshot
{
    public const string DEFAULT_ID = 'mollie_payment_request';

    public function __construct(
        private array $data,
        private readonly DateTimeImmutable $takenAt,
        private readonly DateTimeImmutable $validUntil,
    ) {
    }

    public static function create(DateTimeInterface $takenAt, DateTimeInterface $validUntil, int $version = 2): static
    {
        return new static([], Carbon::instance($takenAt)->toImmutable(), Carbon::instance($validUntil)->toImmutable());
    }

    public function load(array $data): void
    {
        $this->data = $data;
    }


    public function version(): int
    {
        return 2;
    }

    public function takenAt(): DateTimeImmutable
    {
        return $this->takenAt;
    }

    public function validUntil(): DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function data(): array
    {
        return $this->data;
    }
}