<?php
declare(strict_types=1);
namespace Arcates\Core;
final class AdminView
{
    public static function requireUser(): array
    {
        $auth=App::auth();if(!$auth->check()){header('Location: /'.App::config('app.admin_path','yonetim').'/giris');exit;}return $auth->user()??[];
    }
    public static function header(string $title): void
    {
        self::requireUser();$a=Security::escape((string)App::config('app.admin_path','yonetim'));echo '<!doctype html><html lang="tr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.Security::escape($title).'</title><link rel="stylesheet" href="/assets/css/admin.css"><body><div class="admin"><nav class="sidebar"><strong>Arcates</strong><a href="/'.$a.'">Panel</a><a href="/'.$a.'/sayfalar">Sayfalar</a><a href="/'.$a.'/menuler">Menüler</a><a href="/'.$a.'/medya">Medya</a><a href="/'.$a.'/formlar">Formlar</a><a href="/'.$a.'/portfoy">Portföy</a><a href="/'.$a.'/blog">Blog</a><a href="/'.$a.'/referanslar">Referanslar</a><a href="/'.$a.'/rezervasyon">Rezervasyon</a><a href="/'.$a.'/urunler">Ürünler</a><a href="/'.$a.'/siparisler">Siparişler</a><a href="/'.$a.'/kargo">Kargo & Kupon</a><a href="/'.$a.'/kargo-entegrasyon">Kargo API</a><a href="/'.$a.'/pazaryeri">Pazaryeri</a><a href="/'.$a.'/e-belge">e-Belge</a><a href="/'.$a.'/muhasebe">Muhasebe</a><a href="/'.$a.'/istatistik">İstatistik</a><a href="/'.$a.'/bayiler">Bayiler</a><a href="/'.$a.'/emlak">Emlak</a><a href="/'.$a.'/qr-menu">QR Menü</a><a href="/'.$a.'/gonderiler">Gönderiler</a><a href="/'.$a.'/hizmet-fiyat">Hizmet & Fiyat</a><a href="/'.$a.'/subeler">Şubeler</a></nav><main class="main"><h1>'.Security::escape($title).'</h1>';
    }
    public static function footer(): void { echo '</main></div><script src="/assets/js/admin.js" defer></script></body></html>'; }
}
