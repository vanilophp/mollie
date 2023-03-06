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
use Vanilo\Payment\Contracts\Payment;

final class LocaleResolver
{
    private const SUPPORTED_LOCALES = [
        'en_US', 'en_GB', 'nl_NL', 'nl_BE', 'fr_FR', 'fr_BE', 'de_DE', 'de_AT', 'de_CH', 'es_ES', 'ca_ES',
        'pt_PT', 'it_IT', 'nb_NO', 'sv_SE', 'fi_FI', 'da_DK', 'is_IS', 'hu_HU', 'pl_PL', 'lv_LV', 'lt_LT',
    ];

    private static ?self $instance = null;

    public static function makeAnEducatedGuess(Payment $payment): string
    {
        $instance = self::$instance ?: (self::$instance = new self());

        $locale = $instance->guessPaymentLocale($payment);
        if (!is_null($locale)) {
            if ($instance->isSupportedLocale($locale) || $instance->looksLikeALocale($locale)) {
                return $locale;
            }
        }

        $locale = $instance->guessAppLocale($payment);
        if (!is_null($locale)) {
            if ($instance->isSupportedLocale($locale) || $instance->looksLikeALocale($locale)) {
                return $locale;
            }
        }

        return 'en_US';
    }

    private function guessPaymentLocale(Payment $payment): ?string
    {
        $payable = $payment->getPayable();
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

    private function guessAppLocale(Payment $payment): ?string
    {
        if (!is_string($lang = App::currentLocale())) {
            return null;
        }

        return match (strlen($lang)) {
            2 => $lang . '_' . $payment->getPayable()->getBillpayer()->getBillingAddress()->getCountryCode(),
            5 => $lang,
            default => null,
        };
    }

    private function isSupportedLocale(string $locale): bool
    {
        return in_array($locale, self::SUPPORTED_LOCALES);
    }

    private function looksLikeALocale(string $locale): bool
    {
        return
            5 === strlen($locale) &&
            preg_match('/[a-zA-Z][a-zA-Z]_[a-zA-Z][a-zA-Z]/', $locale);
    }
}
