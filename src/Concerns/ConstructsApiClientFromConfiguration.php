<?php

declare(strict_types=1);

/**
 * Contains the ConstructsApiClientFromConfiguration trait.
 *
 * @copyright   Copyright (c) 2023 Vanilo UG
 * @author      Attila Fulop
 * @license     MIT
 * @since       2023-03-07
 *
 */

namespace Vanilo\Mollie\Concerns;

use Mollie\Api\MollieApiClient;
use Vanilo\Mollie\Configuration;

trait ConstructsApiClientFromConfiguration
{
    private MollieApiClient $apiClient;

    public function __construct(
        private Configuration $configuration
    ) {
        $this->apiClient = (new MollieApiClient())->setApiKey($this->configuration->apiKey);
    }
}
