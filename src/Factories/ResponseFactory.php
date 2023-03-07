<?php

declare(strict_types=1);

/**
 * Contains the ResponseFactory class.
 *
 * @copyright   Copyright (c) 2023 Vanilo UG
 * @author      Attila Fulop
 * @license     MIT
 * @since       2023-03-07
 *
 */

namespace Vanilo\Mollie\Factories;

use Vanilo\Mollie\Concerns\ConstructsApiClientFromConfiguration;
use Vanilo\Mollie\Messages\MolliePaymentResponse;

final class ResponseFactory
{
    use ConstructsApiClientFromConfiguration;

    public function create(string $mollieOrderId): MolliePaymentResponse
    {
        $order = $this->apiClient->orders->get($mollieOrderId);

        return MolliePaymentResponse::createFromOrder($order);
    }
}
