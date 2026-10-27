<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Messages;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Order;
use Mollie\Api\Resources\ResourceFactory;
use Vanilo\Contracts\Snapshot;
use Vanilo\Contracts\Snapshottable;
use Vanilo\Mollie\Configuration;
use Vanilo\Payment\Contracts\PaymentRequest;

class MolliePaymentRequest implements PaymentRequest, Snapshottable
{
    private string $view = 'mollie::_request';

    public function __construct(
        private Order $mollieOrder
    ) {
    }

    public static function fromSnapshot(Snapshot $snapshot): static
    {
        /** @var Configuration $gwConfig */
        $gwConfig = app(Configuration::class);

        $mollieOrder = ResourceFactory::createFromApiResult(
            json_decode(json_encode($snapshot->data()['order']), false), // convert the array to \stdClass
            new Order(new MollieApiClient()->setApiKey($gwConfig->apiKey))
        );

        $instance = new static($mollieOrder);

        if (is_string($view = data_get($snapshot->data(), 'view')) && View::exists($view)) {
            $instance->setView($view);
        }

        return $instance;
    }

    public function toSnapshot(): Snapshot
    {
        $snapshot = MolliePaymentRequestSnapshot::create(
            Carbon::make($this->mollieOrder->createdAt),
            Carbon::make($this->mollieOrder->expiresAt),
        );

        $snapshot->load([
            'order' => json_decode(json_encode(get_object_vars($this->mollieOrder)), false),
            'view' => $this->view,
        ]);

        return $snapshot;
    }

    public function getHtmlSnippet(array $options = []): ?string
    {
        return View::make(
            $options['view'] ?? $this->view,
            [
                'url' => $this->mollieOrder->getCheckoutUrl(),
                'autoRedirect' => $options['autoRedirect'] ?? false,
            ]
        )->render();
    }

    public function willRedirect(): bool
    {
        return true;
    }

    public function setView(string $view): self
    {
        $this->view = $view;

        return $this;
    }

    public function getRemoteId(): ?string
    {
        return $this->mollieOrder->id;
    }
}
