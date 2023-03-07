<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Providers;

use Konekt\Concord\BaseModuleServiceProvider;
use Vanilo\Mollie\Configuration;
use Vanilo\Mollie\MolliePaymentGateway;
use Vanilo\Payment\PaymentGateways;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function boot()
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

        $this->publishes([
            $this->getBasePath() . '/' . $this->concord->getConvention()->viewsFolder() =>
            resource_path('views/vendor/mollie'),
            'vanilo-mollie'
        ]);
    }
}
