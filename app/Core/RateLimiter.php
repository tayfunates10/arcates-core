<?php
declare(strict_types=1);

namespace Arcates\Core;

final class RateLimiter
{
    public function __construct(private Database $db) {}

    public function loginAllowed(string $ip, string $username): bool
    {
        $row = $this->db->fetch('SELECT COUNT(*) AS attempts FROM login_attempts WHERE ip_address = ? AND username = ? AND success = 0 AND attempted_at >= (NOW() - INTERVAL 15 MINUTE)', [$ip, mb_strtolower(trim($username))]);
        return (int)($row['attempts'] ?? 0) < 5;
    }

    public function recordLogin(string $ip, string $username, bool $success): void
    {
        $username = mb_strtolower(trim($username));
        $this->db->execute('INSERT INTO login_attempts (ip_address, username, success, attempted_at) VALUES (?, ?, ?, NOW())', [$ip, $username, $success ? 1 : 0]);
        if ($success) { $this->db->execute('DELETE FROM login_attempts WHERE ip_address = ? AND username = ?', [$ip, $username]); }
    }

    public function genericAllowed(string $key, int $max, int $windowSeconds): bool
    {
        $_SESSION['_rate'] ??= [];
        $now = time();
        $bucket = array_values(array_filter($_SESSION['_rate'][$key] ?? [], static fn (int $ts): bool => $ts > $now - $windowSeconds));
        if (count($bucket) >= $max) { $_SESSION['_rate'][$key] = $bucket; return false; }
        $bucket[] = $now;
        $_SESSION['_rate'][$key] = $bucket;
        return true;
    }
}
