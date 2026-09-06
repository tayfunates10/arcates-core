<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\AdminView;use Arcates\Core\App;use Arcates\Core\Csrf;use Arcates\Core\Security;use Arcates\Services\ShipmentService;
final class ShipmentAdminController
{
 public function index(): void
 {
  AdminView::header('Gönderi Takip');$a=Security::escape((string)App::config('app.admin_path','yonetim'));echo '<form class="card" method="post" action="/'.$a.'/gonderiler/ekle">'.Csrf::field().'<h2>Gönderi oluştur</h2><input name="tracking_code" maxlength="32" placeholder="Takip kodu (boşsa otomatik)"><input name="reference_code" maxlength="120" placeholder="Referans"><input name="carrier" maxlength="80" value="Arcates" placeholder="Taşıyıcı"><input name="origin" required maxlength="190" placeholder="Çıkış"><input name="destination" required maxlength="190" placeholder="Varış"><label>Tahmini teslim<input type="date" name="estimated_delivery"></label><button>Oluştur</button></form>';
  foreach(App::db()->fetchAll('SELECT * FROM shipments ORDER BY updated_at DESC,id DESC LIMIT 200') as $s){echo '<section class="card"><h2>'.Security::escape($s['tracking_code']).'</h2><p>'.Security::escape(ShipmentService::label((string)$s['status']).' · '.$s['origin'].' → '.$s['destination']).'</p><p><a target="_blank" href="/gonderi-takip?kod='.rawurlencode((string)$s['tracking_code']).'">Public sorgu</a></p><form method="post" action="/'.$a.'/gonderiler/olay">'.Csrf::field().'<input type="hidden" name="shipment_id" value="'.(int)$s['id'].'"><select name="status">';foreach(ShipmentService::STATUSES as $status)echo '<option value="'.Security::escape($status).'">'.Security::escape(ShipmentService::label($status)).'</option>';echo '</select><input name="location" maxlength="190" placeholder="Konum"><input name="note" maxlength="500" placeholder="Not"><input type="datetime-local" name="event_at"><button>Hareket ekle</button></form>';foreach(App::db()->fetchAll('SELECT status,location,note,event_at FROM shipment_events WHERE shipment_id=? ORDER BY event_at DESC,id DESC LIMIT 20',[(int)$s['id']]) as $e)echo '<p>'.Security::escape((string)$e['event_at'].' · '.ShipmentService::label((string)$e['status']).($e['location']?' · '.$e['location']:'')).'</p>';echo '</section>';}AdminView::footer();
 }
 public function add(): void
 {
  AdminView::requireUser();Csrf::requireValid($_POST['_csrf']??null);$origin=trim((string)($_POST['origin']??''));$destination=trim((string)($_POST['destination']??''));if($origin===''||$destination==='')throw new \RuntimeException('Çıkış ve varış gerekli.');$tracking=strtoupper(trim((string)($_POST['tracking_code']??'')));if($tracking==='')$tracking=ShipmentService::trackingCode();if(!preg_match('/^[A-Z0-9-]{6,32}$/',$tracking))throw new \RuntimeException('Takip kodu geçersiz.');$carrier=trim((string)($_POST['carrier']??'Arcates'))?:'Arcates';$estimated=trim((string)($_POST['estimated_delivery']??''));if($estimated!==''&&!preg_match('/^\d{4}-\d{2}-\d{2}$/',$estimated))throw new \RuntimeException('Tahmini teslim tarihi geçersiz.');App::db()->transaction(function($db)use($tracking,$carrier,$origin,$destination,$estimated): void{$db->execute('INSERT INTO shipments(tracking_code,reference_code,carrier,status,origin,destination,estimated_delivery,created_at,updated_at) VALUES(?,?,?,\'created\',?,?,?,NOW(),NOW())',[$tracking,mb_substr(trim((string)($_POST['reference_code']??'')),0,120)?:null,mb_substr($carrier,0,80),mb_substr($origin,0,190),mb_substr($destination,0,190),$estimated?:null]);$id=(int)$db->lastInsertId();$db->execute('INSERT INTO shipment_events(shipment_id,status,note,event_at,created_at) VALUES(?,\'created\',\'Gönderi kaydı oluşturuldu\',NOW(),NOW())',[$id]);});$this->back();
 }
 public function event(): void
 {
  AdminView::requireUser();Csrf::requireValid($_POST['_csrf']??null);ShipmentService::addEvent((int)($_POST['shipment_id']??0),(string)($_POST['status']??''),$_POST['location']??null,$_POST['note']??null,trim((string)($_POST['event_at']??''))?:null);$this->back();
 }
 private function back(): void{header('Location: /'.App::config('app.admin_path','yonetim').'/gonderiler');}
}
