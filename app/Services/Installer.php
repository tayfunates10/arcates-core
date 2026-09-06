<?php
declare(strict_types=1);

namespace Arcates\Services;

use Arcates\Core\Database;

final class Installer
{
    private const LOCK_DIR = ARCATES_ROOT . '/install';
    private const LOCK_FILE = self::LOCK_DIR . '/install.lock';

    public static function locked(): bool
    {
        return is_file(self::LOCK_FILE);
    }

    public static function hasUsers(array $config): bool
    {
        try {
            $database = new Database($config);
            $database->pdo()->exec((string) file_get_contents(ARCATES_ROOT . '/schema.sql'));
            $row = $database->fetch('SELECT COUNT(*) AS total FROM users');
            return (int) ($row['total'] ?? 0) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function run(array $config, string $email, string $password): void
    {
        if (self::locked()) {
            throw new \RuntimeException('Kurulum kilitli.');
        }

        $database = new Database($config);
        $database->pdo()->exec((string) file_get_contents(ARCATES_ROOT . '/schema.sql'));
        Migrator::run($database);

        $row = $database->fetch('SELECT COUNT(*) AS total FROM users FOR UPDATE');
        if ((int) ($row['total'] ?? 0) > 0) {
            throw new \RuntimeException('Sistem daha önce kurulmuş; yeni kurulum reddedildi.');
        }

        $database->execute(
            'INSERT INTO users (email, password_hash, role, is_active, created_at) VALUES (?, ?, ?, 1, NOW())',
            [mb_strtolower(trim($email)), password_hash($password, PASSWORD_DEFAULT), 'admin']
        );

        if (!is_dir(self::LOCK_DIR)
            && !mkdir(self::LOCK_DIR, 0750, true)
            && !is_dir(self::LOCK_DIR)
        ) {
            throw new \RuntimeException('Kurulum kilit dizini oluşturulamadı.');
        }

        if (file_put_contents(self::LOCK_FILE, date('c') . "\n", LOCK_EX) === false) {
            throw new \RuntimeException('Kurulum kilidi yazılamadı; /install güvenli biçimde kapatılamadı.');
        }
    }
}
