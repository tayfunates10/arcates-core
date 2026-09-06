<?php
declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/bootstrap.php';$fail=[];
foreach(['app/Services/QrCode.php','app/Controllers/QrMenuController.php','app/Controllers/QrMenuAdminController.php','db/migrations/20260906_007_qr_menu.sql'] as $f){if(!is_file($root.'/'.$f))$fail[]='Eksik: '.$f;}
$svg=\Arcates\Services\QrCode::svg('https://example.com/menu/demo/tr');
foreach(['<svg','viewBox=','<path','aria-label="QR kod"'] as $needle){if(!str_contains($svg,$needle))$fail[]='QR SVG çıktısı eksik: '.$needle;}
foreach(['api.qrserver','chart.googleapis','quickchart.io','http://api.'] as $forbidden){if(str_contains($svg,$forbidden))$fail[]='QR çıktısında dış servis bağımlılığı bulundu: '.$forbidden;}
$thrown=false;try{\Arcates\Services\QrCode::svg(str_repeat('x',107));}catch(\RuntimeException){$thrown=true;}if(!$thrown)$fail[]='QR kapasite sınırı kontrollü hata üretmiyor.';
$public=(string)@file_get_contents($root.'/app/Controllers/QrMenuController.php');foreach(['Locale::valid','rtl','image/svg+xml','QrCode::svg','http_response_code(422)'] as $needle){if(!str_contains($public,$needle))$fail[]='QR public kuralı eksik: '.$needle;}
$admin=(string)@file_get_contents($root.'/app/Controllers/QrMenuAdminController.php');foreach(['Csrf::requireValid','Locale::valid','Text::slug','strlen($longest)>106','/uploads/'] as $needle){if(!str_contains($admin,$needle))$fail[]='QR admin güvenliği eksik: '.$needle;}
$migration=(string)@file_get_contents($root.'/db/migrations/20260906_007_qr_menu.sql');foreach(['qr_menus','qr_menu_categories','qr_menu_items','FOREIGN KEY','ON DELETE CASCADE'] as $needle){if(!str_contains($migration,$needle))$fail[]='QR veri modeli eksik: '.$needle;}
$router=(string)@file_get_contents($root.'/public/index.php');foreach(['/menu/{slug}/{locale}','/menu/{slug}/qr.svg','/qr-menu/menu','/qr-menu/kategori','/qr-menu/urun'] as $needle){if(!str_contains($router,$needle))$fail[]='QR route eksik: '.$needle;}
if($fail){fwrite(STDERR,implode(PHP_EOL,$fail).PHP_EOL);exit(1);}echo "QR Menü testleri: OK\n";
