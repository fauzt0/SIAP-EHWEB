<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Ciclos de cobro del ERP (propósito general). Hosting/VPS queda en plugins;
 * dominios, certificados y físicos usan estos mismos ciclos.
 */
final class SalesBillingCycle
{
    public const ONE_TIME   = 'one_time';
    public const MONTHLY    = 'monthly';
    public const SEMIANNUAL = 'semiannual';
    public const YEARLY     = 'yearly';

    public static function normalize(string $cycle): string
    {
        return match ($cycle) {
            'annual' => self::YEARLY,
            default  => $cycle,
        };
    }

    public static function isRecurring(string $cycle): bool
    {
        return in_array(self::normalize($cycle), [self::MONTHLY, self::SEMIANNUAL, self::YEARLY, 'custom'], true);
    }

    public static function nextDue(string $cycle, string $from): string
    {
        $base = strtotime($from) ?: time();

        return match (self::normalize($cycle)) {
            self::MONTHLY    => date('Y-m-d', strtotime('+1 month', $base)),
            self::SEMIANNUAL => date('Y-m-d', strtotime('+6 months', $base)),
            self::YEARLY     => date('Y-m-d', strtotime('+1 year', $base)),
            default          => date('Y-m-d', $base),
        };
    }

    public static function periodEnd(string $cycle, string $start): string
    {
        if (! self::isRecurring($cycle)) {
            return $start;
        }

        return date('Y-m-d', strtotime(self::nextDue($cycle, $start) . ' -1 day'));
    }

    public static function periodLabel(string $cycle, string $date): string
    {
        $ts = strtotime($date) ?: time();

        return match (self::normalize($cycle)) {
            self::MONTHLY    => date('Y-m', $ts),
            self::SEMIANNUAL => date('Y', $ts) . '-H' . ((int) date('n', $ts) <= 6 ? '1' : '2'),
            self::YEARLY     => date('Y', $ts),
            self::ONE_TIME   => 'ONE',
            default          => date('Y-m', $ts),
        };
    }
}
