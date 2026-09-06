<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Arcates\Core\App;
use Arcates\Services\OrderService;

$failures = [];
$ok = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};
$throws = static function (callable $fn): bool {
    try {
        $fn();
        return false;
    } catch (Throwable) {
        return true;
    }
};

$db = App::db();
$suffix = bin2hex(random_bytes(4));
$sku = 'AB-' . strtoupper($suffix);
$coupon = 'ABANDON' . strtoupper($suffix);

$db->execute(
    'INSERT INTO products(locale,name,slug,description,status,base_price,created_at,updated_at) '
    . "VALUES('tr','Runtime Test',?,'Test','published',100,NOW(),NOW())",
    ['abandoned-' . $suffix]
);
$productId = (int) $db->lastInsertId();
$db->execute(
    'INSERT INTO product_variants(product_id,sku,name,price,stock,is_active,created_at,updated_at) '
    . "VALUES(?,?,'Standart',100,5,1,NOW(),NOW())",
    [$productId, $sku]
);
$variantId = (int) $db->lastInsertId();
$db->execute(
    'INSERT INTO coupons(code,type,value,min_total,usage_limit,usage_count,is_active,created_at) '
    . "VALUES(?,'percent',10,0,1,0,1,NOW())",
    [$coupon]
);

$customer = [
    'name' => 'Runtime Test User',
    'identity_number' => '10000000146',
    'email' => 'runtime-abandoned@example.invalid',
    'phone' => '+900000000000',
    'address' => 'Test',
    'city' => 'Test',
    'postal_code' => null,
];
$order = OrderService::create($customer, [$variantId => 1], strtolower($coupon));
$orderId = (int) $order['id'];
$db->execute(
    "UPDATE orders SET payment_status='failed',updated_at='2026-01-01 00:00:00' WHERE id=?",
    [$orderId]
);

$released = OrderService::cancelIfAbandoned($orderId, new DateTimeImmutable('2026-09-06 12:00:00'));
$state = $db->fetch('SELECT status,stock_released FROM orders WHERE id=?', [$orderId]);
$stock = $db->fetch('SELECT stock FROM product_variants WHERE id=?', [$variantId]);
$usage = $db->fetch('SELECT usage_count FROM coupons WHERE code=?', [$coupon]);
$ok($released, 'Eski failed sipariş serbest bırakılmalı.');
$ok(($state['status'] ?? '') === 'cancelled', 'Sipariş cancelled olmalı.');
$ok((int) ($state['stock_released'] ?? 0) === 1, 'stock_released=1 olmalı.');
$ok((int) ($stock['stock'] ?? -1) === 5, 'Stok tam bir kez geri gelmeli.');
$ok((int) ($usage['usage_count'] ?? -1) === 0, 'Kupon hakkı geri verilmeli.');

$again = OrderService::cancelIfAbandoned($orderId, new DateTimeImmutable('2026-09-06 12:00:00'));
$stockAgain = $db->fetch('SELECT stock FROM product_variants WHERE id=?', [$variantId]);
$usageAgain = $db->fetch('SELECT usage_count FROM coupons WHERE code=?', [$coupon]);
$ok(!$again, 'İkinci temizleme no-op olmalı.');
$ok((int) ($stockAgain['stock'] ?? -1) === 5, 'İkinci temizleme stok eklememeli.');
$ok((int) ($usageAgain['usage_count'] ?? -1) === 0, 'Kupon sayacı sıfırın altına inmemeli.');

$pending = OrderService::create($customer, [$variantId => 1]);
$pendingId = (int) $pending['id'];
$db->execute(
    "UPDATE orders SET payment_status='pending',updated_at='2026-01-01 00:00:00' WHERE id=?",
    [$pendingId]
);
$db->execute(
    'INSERT INTO payment_attempts(order_id,provider,provider_token,status,created_at) '
    . "VALUES(?,'iyzico',?,'initialized',NOW())",
    [$pendingId, 'runtime-' . $suffix]
);
$initialized = OrderService::cancelIfAbandoned(
    $pendingId,
    new DateTimeImmutable('2026-09-06 12:00:00')
);
$pendingState = $db->fetch('SELECT status,stock_released FROM orders WHERE id=?', [$pendingId]);
$ok(!$initialized, 'Initialized ödeme denemesi otomatik iptal edilmemeli.');
$ok(($pendingState['status'] ?? '') === 'pending', 'Initialized sipariş pending kalmalı.');
$ok((int) ($pendingState['stock_released'] ?? 1) === 0, 'Initialized sipariş stoğu korunmalı.');

$db->execute(
    "UPDATE orders SET payment_status='paid',status='confirmed',updated_at=NOW() WHERE id=?",
    [$pendingId]
);
$ok(
    $throws(static fn () => OrderService::cancel($pendingId)),
    'Paid sipariş refund tamamlanmadan doğrudan iptal edilememeli.'
);
$paidState = $db->fetch('SELECT status,stock_released FROM orders WHERE id=?', [$pendingId]);
$ok(($paidState['status'] ?? '') === 'confirmed', 'Reddedilen paid iptal durum değiştirmemeli.');
$ok((int) ($paidState['stock_released'] ?? 1) === 0, 'Reddedilen paid iptal stok bırakmamalı.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "Terk edilmiş sipariş MySQL testi: OK\n";
