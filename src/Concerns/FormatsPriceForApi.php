<?php

declare(strict_types=1);

/**
 * Contains the FormatsPriceForApi trait.
 *
 * @copyright   Copyright (c) 2023 Vanilo UG
 * @author      Attila Fulop
 * @license     MIT
 * @since       2023-03-07
 *
 */

namespace Vanilo\Mollie\Concerns;

trait FormatsPriceForApi
{
    private function formatPrice($price): string
    {
        return number_format((float) $price, 2, '.', '');
    }
}
