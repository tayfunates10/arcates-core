<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\App;
final class IcalController
{
 public function export(): void{$unit=(int)($_GET['unit_id']??0);$rows=App::db()->fetchAll('SELECT r.public_code,r.starts_at,r.ends_at,r.guest_name,u.name unit_name FROM reservations r JOIN reservation_units u ON u.id=r.unit_id WHERE r.unit_id=? AND r.status=? ORDER BY r.starts_at',[$unit,'confirmed']);header('Content-Type: text/calendar; charset=UTF-8');header('Content-Disposition: attachment; filename="arcates-reservations.ics"');echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Arcates//Reservations//TR\r\nCALSCALE:GREGORIAN\r\n";foreach($rows as $r){echo "BEGIN:VEVENT\r\nUID:".$this->esc($r['public_code'])."@arcates\r\nDTSTAMP:".gmdate('Ymd\THis\Z')."\r\nDTSTART:".gmdate('Ymd\THis\Z',strtotime($r['starts_at']))."\r\nDTEND:".gmdate('Ymd\THis\Z',strtotime($r['ends_at']))."\r\nSUMMARY:".$this->esc($r['unit_name'].' - '.$r['guest_name'])."\r\nEND:VEVENT\r\n";}echo "END:VCALENDAR\r\n";}
 private function esc(string $v): string{return str_replace(["\\",";",",","\r","\n"],["\\\\","\\;","\\,",'',"\\n"],$v);}
}
