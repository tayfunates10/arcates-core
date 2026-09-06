<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\App;use Arcates\Core\RateLimiter;use Arcates\Core\Security;use Arcates\Services\ShipmentService;
final class ShipmentController
{
 public function index(): void
 {
  $code=strtoupper(trim((string)($_GET['kod']??'')));echo '<!doctype html><html lang="tr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Gönderi Takip</title><link rel="stylesheet" href="/assets/css/theme.css"><body><main class="container"><h1>Gönderi Takip</h1><form class="card" method="get" action="/gonderi-takip"><label>Takip kodu<input name="kod" required maxlength="32" value="'.Security::escape($code).'" autocomplete="off"></label><button>Sorgula</button></form>';
  if($code!=='')$this->result($code);echo '</main></body></html>';
 }
 private function result(string $code): void
 {
  if(!preg_match('/^[A-Z0-9-]{6,32}$/',$code)){echo '<p>Takip kodu biçimi geçersiz.</p>';return;}$limiter=new RateLimiter(App::db());if(!$limiter->genericAllowed('shipment:'.Security::clientIp(),30,600)){http_response_code(429);echo '<p>Çok fazla sorgu yapıldı. Daha sonra tekrar deneyin.</p>';return;}$shipment=App::db()->fetch('SELECT id,tracking_code,carrier,status,origin,destination,current_location,estimated_delivery,created_at,updated_at FROM shipments WHERE tracking_code=? LIMIT 1',[$code]);if(!$shipment){echo '<p>Gönderi bulunamadı.</p>';return;}$events=App::db()->fetchAll('SELECT status,location,note,event_at FROM shipment_events WHERE shipment_id=? ORDER BY event_at DESC,id DESC',[(int)$shipment['id']]);echo '<section class="card"><h2>'.Security::escape($shipment['tracking_code']).'</h2><p><strong>Durum:</strong> '.Security::escape(ShipmentService::label((string)$shipment['status'])).'</p><p><strong>Taşıyıcı:</strong> '.Security::escape($shipment['carrier']).'</p><p><strong>Güzergâh:</strong> '.Security::escape($shipment['origin']).' → '.Security::escape($shipment['destination']).'</p>';if($shipment['current_location'])echo '<p><strong>Son konum:</strong> '.Security::escape($shipment['current_location']).'</p>';if($shipment['estimated_delivery'])echo '<p><strong>Tahmini teslim:</strong> '.Security::escape($shipment['estimated_delivery']).'</p>';echo '</section><section><h2>Hareketler</h2>';foreach($events as $event){echo '<article class="card"><strong>'.Security::escape(ShipmentService::label((string)$event['status'])).'</strong><p>'.Security::escape((string)$event['event_at']).($event['location']?' · '.Security::escape($event['location']):'').'</p>'.($event['note']?'<p>'.Security::escape($event['note']).'</p>':'').'</article>';}echo '</section>';
 }
}
