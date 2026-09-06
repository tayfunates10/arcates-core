<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

$_SERVER['REQUEST_URI'] = '/en/form';
$_SESSION['_csrf'] = 'known-token';
register_shutdown_function(static function (): void {
    fwrite(STDERR, "\nSTATUS=" . http_response_code() . "\n");
});

\Arcates\Core\Csrf::requireValid('wrong-token');
