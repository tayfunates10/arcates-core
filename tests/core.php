<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Arcates\Core\Csrf;
use Arcates\Core\Router;
use Arcates\Core\Security;

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void { if (!$condition) { $failures[] = $message; } };
$assert(Security::escape('<script>alert(1)</script>') === '&lt;script&gt;alert(1)&lt;/script&gt;', 'XSS escape başarısız.');
$_SESSION = [];
$token = Csrf::token();
$assert(strlen($token) >= 64, 'CSRF token zayıf.');
$assert(Csrf::validate($token), 'CSRF doğrulama başarısız.');
$assert(!Csrf::validate('wrong'), 'Yanlış CSRF token kabul edildi.');
$router = new Router();
$hit = false;
$router->get('/test', static function () use (&$hit): void { $hit = true; });
ob_start(); $router->dispatch('GET', '/test?x=1'); ob_end_clean();
$assert($hit, 'Router query string ile route bulamadı.');
$hash = password_hash('GucluSifre!123', PASSWORD_DEFAULT);
$assert(password_verify('GucluSifre!123', $hash), 'password_hash/password_verify başarısız.');
foreach (['app/Core/Database.php','app/Core/Auth.php','app/Core/Csrf.php'] as $file) {
    $src = (string)file_get_contents(dirname(__DIR__) . '/' . $file);
    $assert(!preg_match('/(?:query|fetch|execute)\s*\(\s*[\"\'][^\"\']*\.\s*\$/i', $src), "Potansiyel SQL string birleştirme: {$file}");
}
if ($failures) { fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL); exit(1); }
echo "Core güvenlik testleri: OK\n";
