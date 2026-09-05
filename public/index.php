<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Arcates\Controllers\AdminController;
use Arcates\Controllers\AuthController;
use Arcates\Controllers\HomeController;
use Arcates\Controllers\InstallController;
use Arcates\Core\Router;

$router = new Router();
$admin = trim((string)($GLOBALS['arcates_config']['app']['admin_path'] ?? 'yonetim'), '/');
$router->get('/', [HomeController::class, 'index']);
$router->get('/install', [InstallController::class, 'form']);
$router->post('/install', [InstallController::class, 'install']);
$router->get('/' . $admin . '/giris', [AuthController::class, 'form']);
$router->post('/' . $admin . '/giris', [AuthController::class, 'login']);
$router->get('/' . $admin, [AdminController::class, 'index']);
$router->post('/' . $admin . '/cikis', [AuthController::class, 'logout']);
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
