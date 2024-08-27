<?php

declare(strict_types=1);

/**
 * Contains the LocaleGuesser class.
 *
 * @copyright   Copyright (c) 2023 Vanilo UG
 * @author      Attila Fulop
 * @license     MIT
 * @since       2023-03-06
 *
 */

namespace Vanilo\Mollie\Utils;

use Illuminate\Support\Facades\App;
use Vanilo\Contracts\Payable;

final class LocaleResolver
{
    private const SUPPORTED_LOCALES = [
        'en_US', 'en_GB', 'nl_NL', 'nl_BE', 'fr_FR', 'fr_BE', 'de_DE', 'de_AT', 'de_CH', 'es_ES', 'ca_ES',
        'pt_PT', 'it_IT', 'nb_NO', 'sv_SE', 'fi_FI', 'da_DK', 'is_IS', 'hu_HU', 'pl_PL', 'lv_LV', 'lt_LT',
    ];

    private static ?self $instance = null;

    public static function makeAnEducatedGuess(Payable $payable): string
    {
        $instance = self::$instance ?: (self::$instance = new self());

        $locale = $instance->guessPayableLocale($payable);
        if (null !== $locale && $instance->isSupportedLocale($locale)) {
            return $locale;
        }

        $locale = $instance->guessAppLocale($payable);
        if (null !== $locale && $instance->isSupportedLocale($locale)) {
            return $locale;
        }

        return 'en_US';
    }

    private function guessPayableLocale(Payable $payable): ?string
    {
        if (!method_exists($payable, 'getLanguage')) {
            return null;
        }

        if (!is_string($lang = $payable->getLanguage())) {
            return null;
        }

        return match (strlen($lang)) {
            2 => $lang . '_' . $payable->getBillpayer()->getBillingAddress()->getCountryCode(),
            5 => $lang,
            default => null,
        };
    }

    private function guessAppLocale(Payable $payable): ?string
    {
        if (!is_string($lang = App::currentLocale())) {
            return null;
        }

        return match (strlen($lang)) {
            2 => $lang . '_' . $payable->getBillpayer()->getBillingAddress()->getCountryCode(),
            5 => $lang,
            default => null,
        };
    }

    private function isSupportedLocale(string $locale): bool
    {
        return in_array($locale, self::SUPPORTED_LOCALES);
    }
}
