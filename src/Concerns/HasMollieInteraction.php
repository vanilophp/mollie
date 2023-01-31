<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Concerns;

use Mollie\Api\MollieApiClient;

trait HasMollieInteraction
{
    private MollieApiClient $apiClient;

    public function __construct(
        private string $apiKey,
    )
    {
        $this->apiClient = (new MollieApiClient())->setApiKey($this->apiKey);
    }
}
