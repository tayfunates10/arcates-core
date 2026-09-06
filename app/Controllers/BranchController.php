<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\App;use Arcates\Core\Locale;use Arcates\Core\Security;
final class BranchController
{
 public function index(string $locale='tr'): void
 {
  if(!Locale::valid($locale))$locale='tr';$rows=App::db()->fetchAll('SELECT * FROM branches WHERE locale=? AND is_active=1 ORDER BY sort_order,id',[$locale]);echo '<!doctype html><html lang="'.Security::escape($locale).'" dir="'.($locale==='ar'?'rtl':'ltr').'"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Şubeler</title><link rel="stylesheet" href="/assets/css/theme.css"><body><main class="container"><h1>Şubeler</h1>';foreach($rows as $b){echo '<article class="card"><h2>'.Security::escape($b['name']).'</h2><p>'.Security::escape(trim(($b['district']?$b['district'].' / ':'').$b['city'])).'</p><p>'.Security::escape($b['address']).'</p>';if($b['phone'])echo '<p><a href="tel:'.Security::escape(preg_replace('/[^0-9+]/','',(string)$b['phone'])).'">'.Security::escape($b['phone']).'</a></p>';if($b['email'])echo '<p><a href="mailto:'.Security::escape($b['email']).'">'.Security::escape($b['email']).'</a></p>';if($b['opening_hours'])echo '<p>'.Security::escape($b['opening_hours']).'</p>';if($b['latitude']!==null&&$b['longitude']!==null){$lat=rawurlencode((string)$b['latitude']);$lon=rawurlencode((string)$b['longitude']);echo '<p><a rel="noopener" target="_blank" href="https://www.openstreetmap.org/?mlat='.$lat.'&mlon='.$lon.'#map=16/'.$lat.'/'.$lon.'">Haritada aç</a></p>';}foreach(App::db()->fetchAll('SELECT service_name FROM branch_services WHERE branch_id=? ORDER BY sort_order,id',[(int)$b['id']]) as $s)echo '<span class="tag">'.Security::escape($s['service_name']).'</span>';echo '</article>';}echo '</main></body></html>';
 }
}
