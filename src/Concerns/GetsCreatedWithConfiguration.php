<?php

declare(strict_types=1);

/**
 * Contains the GetsCreatedWithConfiguration trait.
 *
 * @copyright   Copyright (c) 2023 Vanilo UG
 * @author      Attila Fulop
 * @license     MIT
 * @since       2023-03-07
 *
 */

namespace Vanilo\Mollie\Concerns;

use Vanilo\Mollie\Configuration;

trait GetsCreatedWithConfiguration
{
    public function __construct(
        private Configuration $configuration
    ) {
    }
}
