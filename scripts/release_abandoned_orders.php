<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Arcates\Core\App;
use Arcates\Core\Logger;
use Arcates\Services\OrderService;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

$minutes = max(30, min(10080, (int) App::config('commerce.abandoned_order_minutes', 1440)));
$batch = max(1, min(500, (int) App::config('commerce.abandoned_order_batch', 100)));
$cutoff = (new DateTimeImmutable('now'))->modify('-' . $minutes . ' minutes');
$cutoffSql = $cutoff->format('Y-m-d H:i:s');

$candidates = App::db()->fetchAll(
    "SELECT o.id
     FROM orders o
     WHERE o.status='pending'
       AND o.payment_status IN ('pending','failed')
       AND o.stock_released=0
       AND o.updated_at < ?
       AND NOT EXISTS (
           SELECT 1 FROM payment_attempts pa
           WHERE pa.order_id=o.id AND pa.status='initialized'
       )
     ORDER BY o.id
     LIMIT {$batch}",
    [$cutoffSql]
);

$released = 0;
$skipped = 0;
foreach ($candidates as $candidate) {
    try {
        if (OrderService::cancelIfAbandoned((int) $candidate['id'], $cutoff)) {
            $released++;
        } else {
            $skipped++;
        }
    } catch (Throwable $e) {
        $skipped++;
        Logger::error('Abandoned order release failed', [
            'order_id' => (int) $candidate['id'],
            'type' => $e::class,
            'message' => $e->getMessage(),
        ]);
    }
}

$unresolved = App::db()->fetch(
    "SELECT COUNT(*) AS total
     FROM orders o
     WHERE o.status='pending'
       AND o.payment_status='pending'
       AND o.stock_released=0
       AND o.updated_at < ?
       AND EXISTS (
           SELECT 1 FROM payment_attempts pa
           WHERE pa.order_id=o.id AND pa.status='initialized'
       )",
    [$cutoffSql]
);

printf(
    "Terk edilmiş sipariş temizliği: %d stok serbest, %d atlandı, %d sağlayıcı sonucu belirsiz.\n",
    $released,
    $skipped,
    (int) ($unresolved['total'] ?? 0)
);
