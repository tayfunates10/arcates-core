<?php
declare(strict_types=1);
$root=dirname(__DIR__);$fail=[];
foreach(['app/Controllers/BranchController.php','app/Controllers/BranchAdminController.php','db/migrations/20260906_010_branches.sql'] as $f){if(!is_file($root.'/'.$f))$fail[]='Eksik: '.$f;}
$public=(string)@file_get_contents($root.'/app/Controllers/BranchController.php');foreach(['Locale::valid','rtl','openstreetmap.org','branch_services','Security::escape'] as $needle){if(!str_contains($public,$needle))$fail[]='Şube public kuralı eksik: '.$needle;}
$admin=(string)@file_get_contents($root.'/app/Controllers/BranchAdminController.php');foreach(['Csrf::requireValid','FILTER_VALIDATE_EMAIL','coordinate','-90,90','-180,180','Text::slug'] as $needle){if(!str_contains($admin,$needle))$fail[]='Şube admin doğrulaması eksik: '.$needle;}
$migration=(string)@file_get_contents($root.'/db/migrations/20260906_010_branches.sql');foreach(['branches','branch_services','latitude','longitude','opening_hours','FOREIGN KEY','ON DELETE CASCADE'] as $needle){if(!str_contains($migration,$needle))$fail[]='Şube veri modeli eksik: '.$needle;}
$router=(string)@file_get_contents($root.'/public/index.php');foreach(['/subeler/{locale}','/subeler/ekle','/subeler/hizmet'] as $needle){if(!str_contains($router,$needle))$fail[]='Şube route eksik: '.$needle;}
if($fail){fwrite(STDERR,implode(PHP_EOL,$fail).PHP_EOL);exit(1);}echo "Şube Yönetimi testleri: OK\n";
