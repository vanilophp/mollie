<?php

declare(strict_types=1);

/**
 * Contains the HasApiKeyConstructor trait.
 *
 * @copyright   Copyright (c) 2023 Vanilo UG
 * @author      Attila Fulop
 * @license     MIT
 * @since       2023-03-06
 *
 */

namespace Vanilo\Mollie\Concerns;

trait HasApiKeyConstructor
{
    use HasApiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }
}
