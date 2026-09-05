<?php
declare(strict_types=1);

namespace Arcates\Controllers;

use Arcates\Core\Csrf;
use Arcates\Core\Security;
use Arcates\Services\Installer;

final class InstallController
{
    public function form(): void
    {
        if (Installer::locked()) { http_response_code(404); echo 'Kurulum kapalı.'; return; }
        echo '<!doctype html><html lang="tr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Arcates Kurulum</title><body><main><h1>Kurulum</h1><form method="post">' . Csrf::field() . '<label>Admin e-posta <input type="email" name="email" required></label><label>Admin şifre <input type="password" name="password" minlength="12" required></label><button>Kur</button></form></main></body></html>';
    }

    public function install(): void
    {
        if (Installer::locked()) { http_response_code(404); return; }
        Csrf::requireValid($_POST['_csrf'] ?? null);
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = (string)($_POST['password'] ?? '');
        if ($email === false || strlen($password) < 12) { http_response_code(422); echo 'Geçersiz e-posta veya kısa şifre.'; return; }
        Installer::run((array)$GLOBALS['arcates_config'], $email, $password);
        echo '<p>Kurulum tamamlandı. <a href="/' . Security::escape((string)($GLOBALS['arcates_config']['app']['admin_path'] ?? 'yonetim')) . '/giris">Yönetim girişine git</a>.</p>';
    }
}
