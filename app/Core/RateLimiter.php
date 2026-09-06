<?php
declare(strict_types=1);

namespace Arcates\Core;

final class RateLimiter
{
    public function __construct(private Database $db) {}

    public function loginAllowed(string $ip, string $username): bool
    {
        $since = '(NOW() - INTERVAL 15 MINUTE)';
        $username = mb_strtolower(trim($username));

        $pair = $this->db->fetch(
            "SELECT COUNT(*) AS attempts FROM login_attempts
             WHERE ip_address = ? AND username = ? AND success = 0 AND attempted_at >= {$since}",
            [$ip, $username]
        );
        if ((int) ($pair['attempts'] ?? 0) >= 5) {
            return false;
        }

        $perIp = $this->db->fetch(
            "SELECT COUNT(*) AS attempts FROM login_attempts
             WHERE ip_address = ? AND success = 0 AND attempted_at >= {$since}",
            [$ip]
        );
        return (int) ($perIp['attempts'] ?? 0) < 30;
    }

    public function recordLogin(string $ip, string $username, bool $success): void
    {
        $username = mb_strtolower(trim($username));
        $this->db->execute(
            'INSERT INTO login_attempts (ip_address, username, success, attempted_at) VALUES (?, ?, ?, NOW())',
            [$ip, $username, $success ? 1 : 0]
        );
        if ($success) {
            $this->db->execute(
                'DELETE FROM login_attempts WHERE ip_address = ? AND username = ?',
                [$ip, $username]
            );
        }
    }

    public function genericAllowed(string $key, int $max, int $windowSeconds): bool
    {
        $max = max(1, $max);
        $windowSeconds = max(1, $windowSeconds);
        $key = mb_substr(hash('sha256', $key), 0, 64);

        return $this->db->transaction(function (Database $db) use ($key, $max, $windowSeconds): bool {
            $row = $db->fetch(
                'SELECT bucket_key, window_started_at, hits FROM rate_limit_buckets WHERE bucket_key = ? FOR UPDATE',
                [$key]
            );
            $now = time();

            if (!$row) {
                $db->execute(
                    'INSERT INTO rate_limit_buckets(bucket_key, window_started_at, hits, updated_at) VALUES(?, NOW(), 1, NOW())',
                    [$key]
                );
                return true;
            }

            $started = strtotime((string) $row['window_started_at']) ?: 0;
            if ($started <= $now - $windowSeconds) {
                $db->execute(
                    'UPDATE rate_limit_buckets SET window_started_at = NOW(), hits = 1, updated_at = NOW() WHERE bucket_key = ?',
                    [$key]
                );
                return true;
            }

            if ((int) $row['hits'] >= $max) {
                return false;
            }

            $db->execute(
                'UPDATE rate_limit_buckets SET hits = hits + 1, updated_at = NOW() WHERE bucket_key = ?',
                [$key]
            );
            return true;
        });
    }
}
