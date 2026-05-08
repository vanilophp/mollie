<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Tests\Dummies;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Traversable;
use Vanilo\Contracts\Billpayer;
use Vanilo\Contracts\Payable;

/**
 * @method static Order create(array $attributes = [])
 */
class Order extends Model implements Payable
{
    protected $fillable = ['amount', 'currency'];

    protected $table = 'mollie_test_orders';

    public function getPayableId(): string
    {
        return (string) $this->id;
    }

    public function getPayableType(): string
    {
        return self::class;
    }

    public function getTitle(): string
    {
        return 'Order #' . $this->id;
    }

    public function getAmount(): float
    {
        return floatval($this->amount);
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getBillpayer(): ?Billpayer
    {
        return new DummyCustomer();
    }

    public function getNumber(): string
    {
        return $this->id;
    }

    public function getPayableRemoteId(): ?string
    {
        return null;
    }

    public function setPayableRemoteId(string $remoteId): void
    {
        // TODO: Implement setPayableRemoteId() method.
    }

    public static function findByPayableRemoteId(string $remoteId): ?Payable
    {
        return null;
    }

    public function hasItems(): bool
    {
        return !empty($this->items);
    }

    public function getItems(): Traversable
    {
        return collect($this->items);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DumbOrderItem::class, 'order_id', 'id');
    }

    public function addItem(Product $product, int $qty = 1): void
    {
        $this->items()->create([
            'buyable_id' => $product->getId(),
            'buyable_type' => morph_type_of($product),
            'quantity' => $qty,
            'name' => $product->getName(),
            'price' => $product->getPrice(),
        ]);
    }
}
