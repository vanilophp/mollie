<?php

declare(strict_types=1);

/**
 * Contains the HasMollieConstructor trait.
 *
 * @copyright   Copyright (c) 2023 Vanilo UG
 * @author      Attila Fulop
 * @license     MIT
 * @since       2023-03-06
 *
 */

namespace Vanilo\Mollie\Concerns;

trait HasFullMollieConstructor
{
    use HasFullMollieConfiguration;

    public function __construct(string $apiKey, string $redirectUrl, string $webhookUrl)
    {
        $this->apiKey = $apiKey;
        $this->redirectUrl = $redirectUrl;
        $this->webhookUrl = $webhookUrl;
    }
}
