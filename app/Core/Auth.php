<?php
declare(strict_types=1);

namespace Arcates\Core;

final class Auth
{
    public function __construct(private Database $db, private RateLimiter $limiter) {}

    public function attempt(string $email, string $password): bool
    {
        $email = mb_strtolower(trim($email));
        $ip = Security::clientIp();
        if (!$this->limiter->loginAllowed($ip, $email)) { return false; }
        $user = $this->db->fetch('SELECT id, email, password_hash, role, is_active FROM users WHERE email = ? LIMIT 1', [$email]);
        $valid = $user !== null && (int)$user['is_active'] === 1 && password_verify($password, (string)$user['password_hash']);
        $this->limiter->recordLogin($ip, $email, $valid);
        if (!$valid) { return false; }
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$user['id'], 'email' => (string)$user['email'], 'role' => (string)$user['role']];
        return true;
    }

    public function check(): bool { return isset($_SESSION['user']['id']); }
    public function user(): ?array { return $this->check() ? $_SESSION['user'] : null; }
    public function canEdit(): bool { return in_array($_SESSION['user']['role'] ?? '', ['admin', 'editor'], true); }
    public function isAdmin(): bool { return ($_SESSION['user']['role'] ?? '') === 'admin'; }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool)$params['secure'], (bool)$params['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) { session_destroy(); }
    }
}
