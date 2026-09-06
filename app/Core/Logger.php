<?php
declare(strict_types=1);

namespace Arcates\Core;

final class Logger
{
    public static function register(bool $debug = false): void
    {
        ini_set('display_errors', $debug ? '1' : '0');
        error_reporting(E_ALL);
        set_exception_handler(static function (\Throwable $e) use ($debug): void {
            self::error('Unhandled exception', ['type' => $e::class, 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            http_response_code(500);
            echo $debug ? htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : 'Beklenmeyen bir hata oluştu.';
        });
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            self::error('PHP error', compact('severity', 'message', 'file', 'line'));
            return false;
        });
    }

    public static function error(string $message, array $context = []): void
    {
        $dir = defined('ARCATES_ROOT') ? ARCATES_ROOT . '/logs' : dirname(__DIR__, 2) . '/logs';
        if (!is_dir($dir)) { @mkdir($dir, 0750, true); }
        $record = sprintf("[%s] %s %s\n", date('c'), $message, json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        @file_put_contents($dir . '/app-' . date('Y-m-d') . '.log', $record, FILE_APPEND | LOCK_EX);
    }
}
