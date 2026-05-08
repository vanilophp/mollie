<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Tests\Dummies;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Vanilo\Contracts\Buyable;
use Vanilo\Contracts\CheckoutSubjectItem;
use Vanilo\Support\Traits\ConfigurationHasNoSchema;

class DumbOrderItem extends Model implements CheckoutSubjectItem
{
    use ConfigurationHasNoSchema;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $table = 'mollie_test_order_items';

    public function getBuyable(): Buyable
    {
        return $this->buyable;
    }

    public function buyable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function total(): float
    {
        return $this->buyable->getPrice() * $this->quantity;
    }

    public function isShippable(): ?bool
    {
        return true;
    }

    public function configuration(): ?array
    {
        return null;
    }

    public function hasConfiguration(): bool
    {
        return false;
    }

    public function doesntHaveConfiguration(): bool
    {
        return true;
    }
}