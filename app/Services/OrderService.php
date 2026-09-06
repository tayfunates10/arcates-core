<?php
declare(strict_types=1);

namespace Arcates\Services;

use Arcates\Core\App;
use Arcates\Core\Database;

final class OrderService
{
    private const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['preparing', 'cancelled'],
        'preparing' => ['shipped', 'cancelled'],
        'shipped' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    public static function create(array $customer, array $cart, ?string $couponCode = null): array
    {
        if (!$cart) {
            throw new \RuntimeException('Sepet boş.');
        }
        $normalizedCoupon = self::normalizeCoupon($couponCode);

        return App::db()->transaction(function (Database $db) use ($customer, $cart, $normalizedCoupon): array {
            $items = [];
            $subtotal = 0.0;
            foreach ($cart as $variantId => $qty) {
                $qty = max(1, min(99, (int) $qty));
                $variant = $db->fetch(
                    'SELECT v.id,v.product_id,v.sku,v.name,v.price,v.stock,v.is_active,p.name product_name '
                    . 'FROM product_variants v JOIN products p ON p.id=v.product_id WHERE v.id=? FOR UPDATE',
                    [(int) $variantId]
                );
                if (!$variant || !(int) $variant['is_active']) {
                    throw new \RuntimeException('Ürün varyantı kullanılamıyor.');
                }
                if ((int) $variant['stock'] < $qty) {
                    throw new \RuntimeException('Yetersiz stok: ' . $variant['sku']);
                }
                $line = (float) $variant['price'] * $qty;
                $subtotal += $line;
                $items[] = [
                    'product_id' => (int) $variant['product_id'],
                    'variant_id' => (int) $variant['id'],
                    'sku' => $variant['sku'],
                    'name' => $variant['product_name'] . ' - ' . $variant['name'],
                    'unit_price' => (float) $variant['price'],
                    'quantity' => $qty,
                    'line_total' => $line,
                ];
            }

            $discount = self::couponDiscount($db, $normalizedCoupon, $subtotal);
            $shipping = Shipping::fee(max(0, $subtotal - $discount));
            $grand = max(0, $subtotal - $discount + $shipping);
            $code = self::uuid();

            $db->execute(
                'INSERT INTO orders(public_code,customer_name,identity_number,email,phone,address,city,postal_code,'
                . 'subtotal,discount_total,shipping_total,grand_total,coupon_code,created_at,updated_at) '
                . 'VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())',
                [
                    $code,
                    $customer['name'],
                    $customer['identity_number'],
                    $customer['email'],
                    $customer['phone'],
                    $customer['address'],
                    $customer['city'],
                    $customer['postal_code'] ?? null,
                    $subtotal,
                    $discount,
                    $shipping,
                    $grand,
                    $normalizedCoupon ?: null,
                ]
            );
            $orderId = (int) $db->lastInsertId();

            foreach ($items as $item) {
                $db->execute(
                    'INSERT INTO order_items(order_id,product_id,variant_id,sku,name,unit_price,quantity,line_total) '
                    . 'VALUES(?,?,?,?,?,?,?,?)',
                    [
                        $orderId,
                        $item['product_id'],
                        $item['variant_id'],
                        $item['sku'],
                        $item['name'],
                        $item['unit_price'],
                        $item['quantity'],
                        $item['line_total'],
                    ]
                );
                $db->execute(
                    'UPDATE product_variants SET stock=stock-?,updated_at=NOW() WHERE id=?',
                    [$item['quantity'], $item['variant_id']]
                );
            }

            if ($discount > 0 && $normalizedCoupon !== null) {
                $updated = $db->execute(
                    'UPDATE coupons SET usage_count=usage_count+1 WHERE code=?',
                    [$normalizedCoupon]
                );
                if ($updated !== 1) {
                    throw new \RuntimeException('Kupon kullanım sayacı güncellenemedi.');
                }
            }

            return $db->fetch('SELECT * FROM orders WHERE id=?', [$orderId]) ?? [];
        });
    }

    public static function cancel(int $orderId): void
    {
        App::db()->transaction(function (Database $db) use ($orderId): void {
            $order = $db->fetch(
                'SELECT id,status,stock_released FROM orders WHERE id=? FOR UPDATE',
                [$orderId]
            );
            if (!$order) {
                throw new \RuntimeException('Sipariş bulunamadı.');
            }
            self::cancelLocked($db, $order);
        });
    }

    public static function setStatus(int $orderId, string $status): void
    {
        App::db()->transaction(function (Database $db) use ($orderId, $status): void {
            $order = $db->fetch(
                'SELECT id,status,stock_released FROM orders WHERE id=? FOR UPDATE',
                [$orderId]
            );
            if (!$order) {
                throw new \RuntimeException('Sipariş bulunamadı.');
            }
            $current = (string) $order['status'];
            if ($status === $current) {
                return;
            }
            if (!in_array($status, self::TRANSITIONS[$current] ?? [], true)) {
                throw new \RuntimeException("Geçersiz sipariş durum geçişi: {$current} -> {$status}");
            }
            if ($status === 'cancelled') {
                self::cancelLocked($db, $order);
                return;
            }
            $db->execute('UPDATE orders SET status=?,updated_at=NOW() WHERE id=?', [$status, $orderId]);
        });
    }

    private static function cancelLocked(Database $db, array $order): void
    {
        $current = (string) $order['status'];
        if ($current === 'cancelled') {
            return;
        }
        if (!in_array('cancelled', self::TRANSITIONS[$current] ?? [], true)) {
            throw new \RuntimeException("{$current} durumundaki sipariş iptal edilemez.");
        }
        if (!(int) $order['stock_released']) {
            foreach ($db->fetchAll(
                'SELECT variant_id,quantity FROM order_items WHERE order_id=?',
                [(int) $order['id']]
            ) as $item) {
                if ($item['variant_id']) {
                    $db->execute(
                        'UPDATE product_variants SET stock=stock+?,updated_at=NOW() WHERE id=?',
                        [(int) $item['quantity'], (int) $item['variant_id']]
                    );
                }
            }
        }
        $db->execute(
            "UPDATE orders SET stock_released=1,status='cancelled',updated_at=NOW() WHERE id=?",
            [(int) $order['id']]
        );
    }

    private static function couponDiscount(Database $db, ?string $code, float $subtotal): float
    {
        if (!$code) {
            return 0.0;
        }
        $coupon = $db->fetch(
            'SELECT * FROM coupons WHERE code=? AND is_active=1 AND min_total<=? '
            . 'AND (starts_at IS NULL OR starts_at<=NOW()) '
            . 'AND (ends_at IS NULL OR ends_at>=NOW()) '
            . 'AND (usage_limit IS NULL OR usage_count<usage_limit) FOR UPDATE',
            [$code, $subtotal]
        );
        if (!$coupon) {
            return 0.0;
        }
        $value = (float) $coupon['value'];
        $discount = $coupon['type'] === 'percent'
            ? $subtotal * min(100, $value) / 100
            : $value;
        return min($subtotal, max(0, $discount));
    }

    private static function normalizeCoupon(?string $code): ?string
    {
        $normalized = strtoupper(trim((string) $code));
        return $normalized === '' ? null : mb_substr($normalized, 0, 80);
    }

    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4)
            . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }
}
