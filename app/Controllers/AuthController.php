<?php
declare(strict_types=1);

namespace Arcates\Controllers;

use Arcates\Core\App;
use Arcates\Core\Csrf;
use Arcates\Core\Security;

final class AuthController
{
    public function form(): void
    {
        if (App::auth()->check()) { header('Location: /' . App::config('app.admin_path', 'yonetim')); return; }
        echo '<!doctype html><html lang="tr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Yönetim Girişi</title><body><main><h1>Yönetim Girişi</h1><form method="post">' . Csrf::field() . '<label>E-posta <input type="email" name="email" required autocomplete="username"></label><label>Şifre <input type="password" name="password" required autocomplete="current-password"></label><button>Giriş</button></form></main></body></html>';
    }

    public function login(): void
    {
        Csrf::requireValid($_POST['_csrf'] ?? null);
        if (App::auth()->attempt((string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''))) {
            header('Location: /' . App::config('app.admin_path', 'yonetim')); return;
        }
        http_response_code(422);
        echo '<p>Giriş başarısız veya geçici olarak sınırlandırıldı.</p><a href="/' . Security::escape((string)App::config('app.admin_path', 'yonetim')) . '/giris">Tekrar dene</a>';
    }

    public function logout(): void
    {
        Csrf::requireValid($_POST['_csrf'] ?? null);
        App::auth()->logout();
        header('Location: /' . App::config('app.admin_path', 'yonetim') . '/giris');
    }
}
