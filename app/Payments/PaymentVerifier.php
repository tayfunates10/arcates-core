<?php
declare(strict_types=1);

namespace Arcates\Payments;

final class PaymentVerifier
{
    public static function matches(float $expectedTotal, array $result, string $expectedCurrency = 'TRY'): bool
    {
        $expectedMinor = (int) round($expectedTotal * 100);
        $actualMinor = (int) round(((float) ($result['paid_price'] ?? 0)) * 100);
        $currency = strtoupper(trim((string) ($result['currency'] ?? '')));
        if ($currency === 'TL') {
            $currency = 'TRY';
        }
        return $expectedMinor === $actualMinor && $currency === strtoupper($expectedCurrency);
    }
}
