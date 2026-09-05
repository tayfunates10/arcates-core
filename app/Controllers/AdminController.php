<?php
declare(strict_types=1);

namespace Arcates\Controllers;

use Arcates\Core\App;
use Arcates\Core\Csrf;
use Arcates\Core\Security;

final class AdminController
{
    public function index(): void
    {
        $auth = App::auth();
        if (!$auth->check()) { header('Location: /' . App::config('app.admin_path', 'yonetim') . '/giris'); return; }
        $user = $auth->user();
        echo '<!doctype html><html lang="tr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Yönetim</title><body><main><h1>Yönetim</h1><p>' . Security::escape($user['email'] ?? '') . '</p><form method="post" action="/' . Security::escape((string)App::config('app.admin_path', 'yonetim')) . '/cikis">' . Csrf::field() . '<button>Çıkış</button></form></main></body></html>';
    }
}
