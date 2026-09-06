<?php
declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/bootstrap.php';$fail=[];
foreach(['app/Services/ShipmentService.php','app/Controllers/ShipmentController.php','app/Controllers/ShipmentAdminController.php','db/migrations/20260906_008_shipment_tracking.sql'] as $f){if(!is_file($root.'/'.$f))$fail[]='Eksik: '.$f;}
$code=\Arcates\Services\ShipmentService::trackingCode();if(!preg_match('/^ARC-[A-F0-9]{12}$/',$code))$fail[]='Takip kodu yeterince rastgele/standart değil.';
$service=(string)@file_get_contents($root.'/app/Services/ShipmentService.php');foreach(['FOR UPDATE','transaction','shipment_events','UPDATE shipments SET status'] as $needle){if(!str_contains($service,$needle))$fail[]='Gönderi olay tutarlılığı eksik: '.$needle;}
$public=(string)@file_get_contents($root.'/app/Controllers/ShipmentController.php');foreach(['genericAllowed','30,600','tracking_code=?','ShipmentService::label','Security::escape'] as $needle){if(!str_contains($public,$needle))$fail[]='Public takip güvenliği eksik: '.$needle;}foreach(['customer_name','phone','address','reference_code'] as $forbidden){if(str_contains($public,$forbidden))$fail[]='Public takip PII sızdırıyor: '.$forbidden;}
$admin=(string)@file_get_contents($root.'/app/Controllers/ShipmentAdminController.php');foreach(['Csrf::requireValid','ShipmentService::trackingCode','ShipmentService::addEvent','transaction'] as $needle){if(!str_contains($admin,$needle))$fail[]='Gönderi admin kuralı eksik: '.$needle;}
$migration=(string)@file_get_contents($root.'/db/migrations/20260906_008_shipment_tracking.sql');foreach(['shipments','shipment_events','UNIQUE KEY uq_shipments_tracking','FOREIGN KEY','ON DELETE CASCADE'] as $needle){if(!str_contains($migration,$needle))$fail[]='Gönderi veri modeli eksik: '.$needle;}
$router=(string)@file_get_contents($root.'/public/index.php');foreach(['/gonderi-takip','/gonderiler/ekle','/gonderiler/olay'] as $needle){if(!str_contains($router,$needle))$fail[]='Gönderi route eksik: '.$needle;}
if($fail){fwrite(STDERR,implode(PHP_EOL,$fail).PHP_EOL);exit(1);}echo "Gönderi Takip testleri: OK\n";
