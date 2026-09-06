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
        $hashedKey = hash('sha256', $key);

        try {
            return $this->databaseAllowed($hashedKey, $max, $windowSeconds);
        } catch (\Throwable $e) {
            Logger::error('DB rate limiter unavailable; using file fallback', [
                'type' => $e::class,
                'message' => $e->getMessage(),
            ]);
            return $this->fileAllowed($hashedKey, $max, $windowSeconds);
        }
    }

    private function databaseAllowed(string $key, int $max, int $windowSeconds): bool
    {
        return $this->db->transaction(function (Database $db) use ($key, $max, $windowSeconds): bool {
            $row = $db->fetch(
                'SELECT bucket_key, window_started_at, hits FROM rate_limit_buckets WHERE bucket_key = ? FOR UPDATE',
                [$key]
            );
            $now = time();

            if (!$row) {
                $db->execute(
                    'INSERT INTO rate_limit_buckets(bucket_key, window_started_at, hits, updated_at) '
                    . 'VALUES(?, NOW(), 1, NOW())',
                    [$key]
                );
                return true;
            }

            $started = strtotime((string) $row['window_started_at']) ?: 0;
            if ($started <= $now - $windowSeconds) {
                $db->execute(
                    'UPDATE rate_limit_buckets SET window_started_at = NOW(), hits = 1, updated_at = NOW() '
                    . 'WHERE bucket_key = ?',
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

    private function fileAllowed(string $key, int $max, int $windowSeconds): bool
    {
        $dir = ARCATES_ROOT . '/logs/rate-limits';
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
            return false;
        }

        $path = $dir . '/' . $key . '.json';
        $handle = @fopen($path, 'c+');
        if ($handle === false || !flock($handle, LOCK_EX)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            return false;
        }

        try {
            rewind($handle);
            $raw = stream_get_contents($handle);
            $state = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
            $now = time();
            $started = is_array($state) ? (int) ($state['started'] ?? 0) : 0;
            $hits = is_array($state) ? (int) ($state['hits'] ?? 0) : 0;

            if ($started <= $now - $windowSeconds) {
                $started = $now;
                $hits = 0;
            }
            if ($hits >= $max) {
                return false;
            }

            $hits++;
            ftruncate($handle, 0);
            rewind($handle);
            $written = fwrite($handle, json_encode(['started' => $started, 'hits' => $hits], JSON_THROW_ON_ERROR));
            fflush($handle);
            return $written !== false;
        } catch (\Throwable) {
            return false;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
