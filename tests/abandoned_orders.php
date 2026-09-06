<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Arcates\Services\OrderService;

$fail = [];
$cutoff = new DateTimeImmutable('2026-09-06 12:00:00');
$base = [
    'status' => 'pending',
    'payment_status' => 'failed',
    'stock_released' => 0,
    'updated_at' => '2026-09-06 10:00:00',
];

if (!OrderService::isAbandonedCandidate($base, $cutoff)) {
    $fail[] = 'Süresi geçmiş başarısız ödeme terk edilmiş aday olmalı.';
}

$cases = [
    'fresh' => array_replace($base, ['updated_at' => '2026-09-06 11:59:59']),
    'paid' => array_replace($base, ['payment_status' => 'paid']),
    'confirmed' => array_replace($base, ['status' => 'confirmed']),
    'released' => array_replace($base, ['stock_released' => 1]),
];
foreach ($cases as $name => $case) {
    if (OrderService::isAbandonedCandidate($case, $cutoff)) {
        $fail[] = "{$name} sipariş terk edilmiş aday olmamalı.";
    }
}

$pending = array_replace($base, ['payment_status' => 'pending']);
if (!OrderService::isAbandonedCandidate($pending, $cutoff)) {
    $fail[] = 'Eski pending sipariş temel aday olmalı; initialized ödeme filtresi cron/DB kilidinde uygulanır.';
}

$script = (string) file_get_contents(dirname(__DIR__) . '/scripts/release_abandoned_orders.php');
foreach ([
    "NOT EXISTS (",
    "pa.status='initialized'",
    'OrderService::cancelIfAbandoned',
    'payment_status IN',
    'stock_released=0',
] as $needle) {
    if (!str_contains($script, $needle)) {
        $fail[] = 'Terk edilmiş sipariş cron güvenliği eksik: ' . $needle;
    }
}

$orderService = (string) file_get_contents(dirname(__DIR__) . '/app/Services/OrderService.php');
foreach ([
    "payment_status'] === 'paid'",
    'GREATEST(usage_count-1,0)',
    'FOR UPDATE',
] as $needle) {
    if (!str_contains($orderService, $needle)) {
        $fail[] = 'Sipariş iptal güvenliği eksik: ' . $needle;
    }
}

if ($fail) {
    fwrite(STDERR, implode(PHP_EOL, $fail) . PHP_EOL);
    exit(1);
}

echo "Terk edilmiş sipariş davranış testleri: OK\n";
