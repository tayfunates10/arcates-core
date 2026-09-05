<?php
declare(strict_types=1);
namespace Arcates\Services;
use Arcates\Core\Database;use Arcates\Core\Security;
final class ReservationService
{
 public function __construct(private Database $db){}
 public static function overlaps(string $aStart,string $aEnd,string $bStart,string $bEnd): bool{return strtotime($aStart)<strtotime($bEnd)&&strtotime($aEnd)>strtotime($bStart);}
 public function available(int $unitId,string $start,string $end): bool{return $this->db->fetch('SELECT id FROM reservations WHERE unit_id=? AND status IN (?,?) AND starts_at < ? AND ends_at > ? LIMIT 1',[$unitId,'pending','confirmed',$end,$start])===null;}
 public function create(array $data): array
 {
  $unitId=(int)($data['unit_id']??0);$start=$this->date((string)($data['starts_at']??''));$end=$this->date((string)($data['ends_at']??''));if(strtotime($end)<=strtotime($start))throw new \InvalidArgumentException('Bitiş başlangıçtan sonra olmalı.');
  return $this->db->transaction(function(Database $db)use($unitId,$start,$end,$data):array{
   $unit=$db->fetch('SELECT id,name,unit_type,capacity,base_price,currency FROM reservation_units WHERE id=? AND is_active=1 FOR UPDATE',[$unitId]);if(!$unit)throw new \RuntimeException('Birim bulunamadı.');
   $conflict=$db->fetch('SELECT id FROM reservations WHERE unit_id=? AND status IN (?,?) AND starts_at < ? AND ends_at > ? LIMIT 1 FOR UPDATE',[$unitId,'pending','confirmed',$end,$start]);if($conflict)throw new \RuntimeException('Seçilen tarih aralığı dolu.');
   $guests=max(1,(int)($data['guests']??1));if($guests>(int)$unit['capacity'])throw new \RuntimeException('Kapasite aşıldı.');$amount=$this->price($unit,$start,$end);$code=strtoupper(Security::randomToken(8));
   $db->execute('INSERT INTO reservations(public_code,unit_id,starts_at,ends_at,guest_name,guest_email,guest_phone,guests,status,total_amount,currency,notes,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())',[$code,$unitId,$start,$end,trim((string)$data['guest_name']),mb_strtolower(trim((string)$data['guest_email'])),trim((string)($data['guest_phone']??'')),$guests,'pending',$amount,$unit['currency'],trim((string)($data['notes']??''))]);
   return ['public_code'=>$code,'unit_name'=>$unit['name'],'starts_at'=>$start,'ends_at'=>$end,'total_amount'=>$amount,'currency'=>$unit['currency'],'guest_email'=>mb_strtolower(trim((string)$data['guest_email']))];
  });
 }
 public function setStatus(int $id,string $status): void{if(!in_array($status,['confirmed','cancelled'],true))throw new \InvalidArgumentException('Geçersiz durum.');$this->db->execute('UPDATE reservations SET status=?,updated_at=NOW() WHERE id=?',[$status,$id]);}
 private function price(array $unit,string $start,string $end): float{$season=$this->db->fetch('SELECT price FROM seasonal_prices WHERE unit_id=? AND starts_on<=? AND ends_on>=? ORDER BY starts_on DESC LIMIT 1',[(int)$unit['id'],substr($start,0,10),substr($start,0,10)]);$rate=(float)($season['price']??$unit['base_price']);$seconds=max(3600,strtotime($end)-strtotime($start));$mult=$unit['unit_type']==='room'?max(1,(int)ceil($seconds/86400)):1;return round($rate*$mult,2);}
 private function date(string $value): string{$dt=\DateTimeImmutable::createFromFormat('Y-m-d\TH:i',$value)?:\DateTimeImmutable::createFromFormat('Y-m-d H:i:s',$value);if(!$dt)throw new \InvalidArgumentException('Geçersiz tarih.');return $dt->format('Y-m-d H:i:s');}
}
