<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Messages;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Order;
use Mollie\Api\Resources\ResourceFactory;
use Vanilo\Mollie\Configuration;
use Vanilo\Payment\Contracts\PaymentRequest;
use Vanilo\Snapshot\Contracts\SnapshotPayload;
use Vanilo\Snapshot\Contracts\Snapshottable;
use Vanilo\Snapshot\SnapshotMetadata;

class MolliePaymentRequest implements PaymentRequest, Snapshottable
{
    private string $view = 'mollie::_request';

    public function __construct(
        private Order $mollieOrder
    ) {
    }

    public static function fromSnapshotPayload(SnapshotPayload $payload): static
    {
        /** @var Configuration $gwConfig */
        $gwConfig = app(Configuration::class);

        $data = $payload->toArray();
        $mollieOrder = ResourceFactory::createFromApiResult(
            json_decode(json_encode($data['order']), false), // convert the array to \stdClass
            new Order(new MollieApiClient()->setApiKey($gwConfig->apiKey))
        );

        $instance = new static($mollieOrder);

        if (is_string($view = data_get($data, 'view')) && View::exists($view)) {
            $instance->setView($view);
        }

        return $instance;
    }

    public function toSnapshotPayload(): MolliePaymentRequestSnapshotPayload
    {
        return MolliePaymentRequestSnapshotPayload::fromArray(
            [
                'order' => json_decode(json_encode(get_object_vars($this->mollieOrder)), false),
                'view' => $this->view,
            ],
            new SnapshotMetadata(
                MolliePaymentRequestSnapshotPayload::VERSION,
                Carbon::make($this->mollieOrder->createdAt)->toDateTimeImmutable(),
                Carbon::make($this->mollieOrder->expiresAt)->toDateTimeImmutable(),
            ),
        );
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
