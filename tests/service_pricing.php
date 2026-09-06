<?php
declare(strict_types=1);
$root=dirname(__DIR__);$fail=[];
foreach(['app/Controllers/ServicePricingController.php','app/Controllers/ServicePricingAdminController.php','db/migrations/20260906_009_service_pricing.sql'] as $f){if(!is_file($root.'/'.$f))$fail[]='Eksik: '.$f;}
$public=(string)@file_get_contents($root.'/app/Controllers/ServicePricingController.php');foreach(['Locale::valid','rtl','service_offers','service_prices','Security::escape'] as $needle){if(!str_contains($public,$needle))$fail[]='Hizmet public kuralı eksik: '.$needle;}
$admin=(string)@file_get_contents($root.'/app/Controllers/ServicePricingAdminController.php');foreach(['Csrf::requireValid','Locale::valid','Text::slug','preg_match','service_prices'] as $needle){if(!str_contains($admin,$needle))$fail[]='Hizmet admin kuralı eksik: '.$needle;}
$migration=(string)@file_get_contents($root.'/db/migrations/20260906_009_service_pricing.sql');foreach(['service_offers','service_prices','currency','unit_label','is_featured','FOREIGN KEY','ON DELETE CASCADE'] as $needle){if(!str_contains($migration,$needle))$fail[]='Hizmet veri modeli eksik: '.$needle;}
$router=(string)@file_get_contents($root.'/public/index.php');foreach(['/hizmet-fiyatlari/{locale}','/hizmet-fiyat/hizmet','/hizmet-fiyat/fiyat'] as $needle){if(!str_contains($router,$needle))$fail[]='Hizmet route eksik: '.$needle;}
if($fail){fwrite(STDERR,implode(PHP_EOL,$fail).PHP_EOL);exit(1);}echo "Hizmet Fiyatlandırma testleri: OK\n";
