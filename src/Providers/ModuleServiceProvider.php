<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Providers;

use Konekt\Concord\BaseModuleServiceProvider;
use Vanilo\Mollie\Configuration;
use Vanilo\Mollie\Messages\MolliePaymentRequestSnapshot;
use Vanilo\Mollie\MolliePaymentGateway;
use Vanilo\Payment\PaymentGateways;
use Vanilo\Support\SnapshotTypes;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->bind(Configuration::class, function ($app) {
            return Configuration::fromArray($this->config());
        });
    }


    public function boot(): void
    {
        parent::boot();

        if ($this->config('gateway.register', true)) {
            PaymentGateways::register(
                $this->config('gateway.id', MolliePaymentGateway::DEFAULT_ID),
                MolliePaymentGateway::class
            );
        }

        if ($this->config('bind', true)) {
            $this->app->bind(MolliePaymentGateway::class, function ($app) {
                return new MolliePaymentGateway(
                    Configuration::fromArray($this->config())
                );
            });
        }

        SnapshotTypes::register(MolliePaymentRequestSnapshot::DEFAULT_ID, MolliePaymentRequestSnapshot::class);

        $this->publishes([
            $this->getBasePath() . '/' . $this->concord->getConvention()->viewsFolder() =>
            resource_path('views/vendor/mollie'),
            'vanilo-mollie'
        ]);
    }
}
