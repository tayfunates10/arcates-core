<?php
declare(strict_types=1);

namespace Arcates\Services;

use Arcates\Core\Database;

final class Installer
{
    private const LOCK_DIR = ARCATES_ROOT . '/install';
    private const LOCK_FILE = self::LOCK_DIR . '/install.lock';
    private const RUNNING_FILE = self::LOCK_DIR . '/install.running';

    public static function locked(): bool
    {
        return is_file(self::LOCK_FILE);
    }

    public static function hasUsers(array $config): bool
    {
        try {
            $database = new Database($config);
            return $database->fetch('SELECT id FROM users LIMIT 1') !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function run(array $config, string $email, string $password): void
    {
        self::ensureLockDirectory();
        $guard = @fopen(self::RUNNING_FILE, 'x');
        if ($guard === false) {
            throw new \RuntimeException('Başka bir kurulum işlemi devam ediyor.');
        }

        try {
            if (self::locked()) {
                throw new \RuntimeException('Kurulum kilitli.');
            }

            $database = new Database($config);
            $database->pdo()->exec((string) file_get_contents(ARCATES_ROOT . '/schema.sql'));
            Migrator::run($database);

            if ($database->fetch('SELECT id FROM users LIMIT 1 FOR UPDATE') !== null) {
                throw new \RuntimeException('Sistem daha önce kurulmuş; yeni kurulum reddedildi.');
            }

            $database->execute(
                'INSERT INTO users (email, password_hash, role, is_active, created_at) VALUES (?, ?, ?, 1, NOW())',
                [mb_strtolower(trim($email)), password_hash($password, PASSWORD_DEFAULT), 'admin']
            );

            if (file_put_contents(self::LOCK_FILE, date('c') . "\n", LOCK_EX) === false) {
                throw new \RuntimeException('Kurulum kilidi yazılamadı; /install güvenli biçimde kapatılamadı.');
            }
        } finally {
            fclose($guard);
            @unlink(self::RUNNING_FILE);
        }
    }

    private static function ensureLockDirectory(): void
    {
        if (!is_dir(self::LOCK_DIR)
            && !mkdir(self::LOCK_DIR, 0750, true)
            && !is_dir(self::LOCK_DIR)
        ) {
            throw new \RuntimeException('Kurulum kilit dizini oluşturulamadı.');
        }
    }
}
