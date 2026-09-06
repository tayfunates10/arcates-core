<?php
declare(strict_types=1);

namespace Arcates\Services;

final class Cart
{
    private const KEY = 'arcates_cart';
    private const MAX_DISTINCT_ITEMS = 100;

    public static function add(int $variantId, int $quantity = 1): void
    {
        $quantity = max(1, min(99, $quantity));
        $items = self::raw();
        if (!array_key_exists($variantId, $items) && count($items) >= self::MAX_DISTINCT_ITEMS) {
            throw new \RuntimeException('Sepette en fazla 100 farklı ürün varyantı bulunabilir.');
        }
        $items[$variantId] = min(99, ($items[$variantId] ?? 0) + $quantity);
        $_SESSION[self::KEY] = $items;
    }

    public static function set(int $variantId, int $quantity): void
    {
        $items = self::raw();
        if ($quantity <= 0) {
            unset($items[$variantId]);
        } else {
            if (!array_key_exists($variantId, $items) && count($items) >= self::MAX_DISTINCT_ITEMS) {
                throw new \RuntimeException('Sepette en fazla 100 farklı ürün varyantı bulunabilir.');
            }
            $items[$variantId] = min(99, $quantity);
        }
        $_SESSION[self::KEY] = $items;
    }

    public static function clear(): void
    {
        unset($_SESSION[self::KEY]);
    }

    public static function raw(): array
    {
        return is_array($_SESSION[self::KEY] ?? null) ? $_SESSION[self::KEY] : [];
    }
}
