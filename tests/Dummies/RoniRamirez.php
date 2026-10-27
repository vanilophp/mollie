<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Tests\Dummies;

use Vanilo\Contracts\Address;

class RoniRamirez implements \Vanilo\Contracts\Billpayer
{
    public function isEuRegistered(): bool
    {
        return false;
    }

    public function getBillingAddress(): Address
    {
        return new \Vanilo\Support\Dto\Address(
            'Roni Ramirez',
            'NL',
            'Tulpstraat 109',
            'Rijswijk',
            '2282 NP',
        );
    }

    public function getEmail(): ?string
    {
        return 'roni.ramirez.sample@vanilo.net';
    }

    public function getPhone(): ?string
    {
        return null;
    }

    public function getName(): string
    {
        return 'Roni Ramirez';
    }

    public function isOrganization(): bool
    {
        return false;
    }

    public function isIndividual(): bool
    {
        return true;
    }

    public function getCompanyName(): ?string
    {
        return null;
    }

    public function getTaxNumber(): ?string
    {
        return null;
    }

    public function getFirstName(): ?string
    {
        return 'Roni';
    }

    public function getLastName(): ?string
    {
        return 'Ramirez';
    }

    public function getFullName(): string
    {
        return 'Roni Ramirez';
    }
}