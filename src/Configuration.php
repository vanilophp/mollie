<?php

declare(strict_types=1);

/**
 * Contains the Configuration class.
 *
 * @copyright   Copyright (c) 2023 Vanilo UG
 * @author      Attila Fulop
 * @license     MIT
 * @since       2023-03-07
 *
 */

namespace Vanilo\Mollie;

final class Configuration
{
    public function __construct(
        public readonly string $apiKey,
        public readonly string $redirectUrl,
        public readonly string $webhookUrl,
    ) {
    }

    public static function fromArray(array $config): self
    {
        return new self(
            $config['api_key'],
            $config['return_url'],
            $config['webhook_url'],
        );
    }
}
