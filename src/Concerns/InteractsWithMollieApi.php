<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Concerns;

use LogicException;
use Mollie\Api\MollieApiClient;

trait InteractsWithMollieApi
{
    private ?MollieApiClient $_apiClient = null;

    public function mollie()
    {
        if (null === $this->_apiClient) {
            if (!property_exists($this, 'apiKey')) {
                throw new LogicException(sprintf('The class %s must have an `string apiKey` property', get_class()));
            }

            $this->_apiClient = (new MollieApiClient())->setApiKey($this->apiKey);
        }

        return $this->_apiClient;
    }
}
