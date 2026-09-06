<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use Arcates\Core\WhatsApp;
$fail=[];$assert=static function(bool $c,string $m)use(&$fail){if(!$c)$fail[]=$m;};
$contact=(string)file_get_contents(dirname(__DIR__).'/app/Controllers/ContactController.php');foreach(['Csrf::requireValid','website','kvkk_consent','genericAllowed'] as $needle)$assert(str_contains($contact,$needle),'İletişim koruması eksik: '.$needle);
$purge=(string)file_get_contents(dirname(__DIR__).'/scripts/purge_forms.php');$assert(str_contains($purge,'created_at < ?'),'Form saklama temizliği prepared statement değil.');
$GLOBALS['arcates_config']['contact']['whatsapp_phone']='+90 (555) 123 45 67';$GLOBALS['arcates_config']['contact']['whatsapp_message']='Merhaba dünya';$link=WhatsApp::link();$assert($link==='https://wa.me/905551234567?text=Merhaba%20d%C3%BCnya','WhatsApp linki yanlış.');
$gallery=(string)file_get_contents(dirname(__DIR__).'/app/Controllers/GalleryAdminController.php');$assert(str_contains($gallery,'Upload::image'),'Galeri güvenli upload kullanmıyor.');
$blog=(string)file_get_contents(dirname(__DIR__).'/app/Controllers/BlogController.php');$assert(str_contains($blog,'Security::escape'),'Blog çıktısı escape edilmiyor.');
if($fail){fwrite(STDERR,implode(PHP_EOL,$fail).PHP_EOL);exit(1);}echo "Dönüşüm testleri: OK\n";
