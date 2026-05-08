<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Tests\Dummies;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Vanilo\Contracts\Buyable;
use Vanilo\Support\Traits\BuyableNoImage;

class Product extends Model implements Buyable
{
    use BuyableNoImage;

    protected $table = 'mollie_test_products';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function getId(): string|int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getOriginalPrice(): ?float
    {
        return null;
    }

    public function hasAHigherOriginalPrice(): bool
    {
        return false;
    }

    public function addSale(Carbon $date, float|int $units = 1): void
    {
        // TODO: Implement addSale() method.
    }

    public function removeSale(float|int $units = 1): void
    {
        // TODO: Implement removeSale() method.
    }

    public function morphTypeName(): string
    {
        return 'product';
    }
}