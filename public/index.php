<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use Arcates\Controllers\AdminController;use Arcates\Controllers\AuthController;use Arcates\Controllers\HomeController;use Arcates\Controllers\InstallController;use Arcates\Controllers\MediaController;use Arcates\Controllers\MenuController;use Arcates\Controllers\PageAdminController;use Arcates\Controllers\PublicPageController;use Arcates\Controllers\SeoController;use Arcates\Core\Router;
$router=new Router();$admin=trim((string)($GLOBALS['arcates_config']['app']['admin_path']??'yonetim'),'/');
$router->get('/',[HomeController::class,'index']);$router->get('/install',[InstallController::class,'form']);$router->post('/install',[InstallController::class,'install']);
$router->get('/sitemap.xml',[SeoController::class,'sitemap']);$router->get('/robots.txt',[SeoController::class,'robots']);
$router->get('/'.$admin.'/giris',[AuthController::class,'form']);$router->post('/'.$admin.'/giris',[AuthController::class,'login']);$router->get('/'.$admin,[AdminController::class,'index']);$router->post('/'.$admin.'/cikis',[AuthController::class,'logout']);
$router->get('/'.$admin.'/sayfalar',[PageAdminController::class,'index']);$router->get('/'.$admin.'/sayfalar/yeni',[PageAdminController::class,'createForm']);$router->get('/'.$admin.'/sayfalar/duzenle',[PageAdminController::class,'editForm']);$router->post('/'.$admin.'/sayfalar/kaydet',[PageAdminController::class,'save']);$router->post('/'.$admin.'/sayfalar/sil',[PageAdminController::class,'delete']);
$router->get('/'.$admin.'/medya',[MediaController::class,'index']);$router->post('/'.$admin.'/medya',[MediaController::class,'upload']);$router->get('/'.$admin.'/menuler',[MenuController::class,'index']);$router->post('/'.$admin.'/menuler',[MenuController::class,'add']);$router->post('/'.$admin.'/menuler/sil',[MenuController::class,'delete']);
$router->get('/{locale}/{slug}',[PublicPageController::class,'show']);$router->dispatch($_SERVER['REQUEST_METHOD']??'GET',$_SERVER['REQUEST_URI']??'/');
