<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use Arcates\Services\ReservationService;
$fail=[];$assert=static function(bool $c,string $m)use(&$fail){if(!$c)$fail[]=$m;};
$assert(ReservationService::overlaps('2026-09-10 10:00:00','2026-09-12 10:00:00','2026-09-11 09:00:00','2026-09-13 09:00:00'),'Çakışan rezervasyon algılanmadı.');
$assert(!ReservationService::overlaps('2026-09-10 10:00:00','2026-09-11 10:00:00','2026-09-11 10:00:00','2026-09-12 10:00:00'),'Bitişik rezervasyon yanlış çakıştı.');
$src=(string)file_get_contents(dirname(__DIR__).'/app/Services/ReservationService.php');foreach(['FOR UPDATE','starts_at < ?','ends_at > ?','status IN (?,?)'] as $n)$assert(str_contains($src,$n),'Çift rezervasyon kilidi eksik: '.$n);
$form=(string)file_get_contents(dirname(__DIR__).'/app/Controllers/ReservationController.php');$assert(str_contains($form,'Csrf::requireValid')&&str_contains($form,'genericAllowed'),'Rezervasyon form güvenliği eksik.');
if($fail){fwrite(STDERR,implode(PHP_EOL,$fail).PHP_EOL);exit(1);}echo "Rezervasyon testleri: OK\n";
