<?php
declare(strict_types=1);
namespace Arcates\Services;
use Arcates\Core\App;use Arcates\Core\Database;
final class ShipmentService
{
 public const STATUSES=['created','picked_up','in_transit','out_for_delivery','delivered','cancelled','exception'];
 public static function trackingCode(): string{return 'ARC-'.strtoupper(bin2hex(random_bytes(6)));}
 public static function validStatus(string $status): bool{return in_array($status,self::STATUSES,true);}
 public static function label(string $status): string{return match($status){'created'=>'Oluşturuldu','picked_up'=>'Teslim alındı','in_transit'=>'Yolda','out_for_delivery'=>'Dağıtımda','delivered'=>'Teslim edildi','cancelled'=>'İptal','exception'=>'Sorun var',default=>'Bilinmiyor'};}
 public static function addEvent(int $shipmentId,string $status,?string $location,?string $note,?string $eventAt=null): void
 {
  if(!self::validStatus($status))throw new \RuntimeException('Geçersiz gönderi durumu.');$location=self::clean($location,190);$note=self::clean($note,500);$eventAt=$eventAt&&preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?$/',$eventAt)?str_replace('T',' ',$eventAt):date('Y-m-d H:i:s');
  App::db()->transaction(function(Database $db)use($shipmentId,$status,$location,$note,$eventAt): void{$shipment=$db->fetch('SELECT id FROM shipments WHERE id=? FOR UPDATE',[$shipmentId]);if(!$shipment)throw new \RuntimeException('Gönderi bulunamadı.');$db->execute('INSERT INTO shipment_events(shipment_id,status,location,note,event_at,created_at) VALUES(?,?,?,?,?,NOW())',[$shipmentId,$status,$location,$note,$eventAt]);$db->execute('UPDATE shipments SET status=?,current_location=?,updated_at=NOW() WHERE id=?',[$status,$location,$shipmentId]);});
 }
 private static function clean(?string $value,int $max): ?string{$value=trim((string)$value);return $value===''?null:mb_substr($value,0,$max);}
}
