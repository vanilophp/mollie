<?php

declare(strict_types=1);

/**
 * Contains the HasMollieConfiguration trait.
 *
 * @copyright   Copyright (c) 2023 Vanilo UG
 * @author      Attila Fulop
 * @license     MIT
 * @since       2023-03-06
 *
 */

namespace Vanilo\Mollie\Concerns;

trait HasFullMollieConfiguration
{
    use HasApiKey;

    private string $redirectUrl;

    private string $webhookUrl;
}
