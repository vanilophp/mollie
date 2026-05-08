<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Tests\Dummies;

use Vanilo\Contracts\Address;
use Vanilo\Contracts\Billpayer;

class DummyCustomer implements Billpayer
{
    public function isEuRegistered(): bool
    {
        return false;
    }

    public function getBillingAddress(): Address
    {
        return new \Vanilo\Support\Dto\Address(
            'Nick Grabowski',
            'PL',
            'Ulica Gorowa 1',
            'Krakow',
            '30-000',
        );
    }

    public function getEmail(): ?string
    {
        return 'nick.grabowski@intermouse.pl';
    }

    public function getPhone(): ?string
    {
        return null;
    }

    public function getName(): string
    {
        // TODO: Implement getName() method.
    }

    public function isOrganization(): bool
    {
        // TODO: Implement isOrganization() method.
    }

    public function isIndividual(): bool
    {
        // TODO: Implement isIndividual() method.
    }

    public function getCompanyName(): ?string
    {
        return null;
    }

    public function getTaxNumber(): ?string
    {
        // TODO: Implement getTaxNumber() method.
    }

    public function getFirstName(): ?string
    {
        return 'Nick';
    }

    public function getLastName(): ?string
    {
        return 'Grabowski';
    }

    public function getFullName(): string
    {
        return 'Nick Grabowski';
    }
}
