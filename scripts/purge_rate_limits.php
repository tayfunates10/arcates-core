<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Arcates\Core\App;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

$hours = isset($argv[1]) ? (int) $argv[1] : 48;
$hours = max(1, min(720, $hours));
$cutoff = (new DateTimeImmutable('now'))->modify('-' . $hours . ' hours');

$deleted = App::db()->execute(
    'DELETE FROM rate_limit_buckets WHERE updated_at < ?',
    [$cutoff->format('Y-m-d H:i:s')]
);

printf("Rate-limit temizliği: %d eski bucket silindi (%d saat).\n", $deleted, $hours);
