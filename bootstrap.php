<?php
declare(strict_types=1);

const ARCATES_ROOT = __DIR__;

spl_autoload_register(static function (string $class): void {
    $prefix = 'Arcates\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = ARCATES_ROOT . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$configFile = ARCATES_ROOT . '/config.php';
if (!is_file($configFile)) {
    $configFile = ARCATES_ROOT . '/config.example.php';
}
$config = require $configFile;
$GLOBALS['arcates_config'] = $config;

date_default_timezone_set((string)($config['app']['timezone'] ?? 'Europe/Istanbul'));

\Arcates\Core\Logger::register((bool)($config['app']['debug'] ?? false));
\Arcates\Core\Security::startSession($config);
