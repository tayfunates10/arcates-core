<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\App;use Arcates\Core\Locale;use Arcates\Core\Security;
final class ServicePricingController
{
 public function index(string $locale='tr'): void
 {
  if(!Locale::valid($locale))$locale='tr';$services=App::db()->fetchAll('SELECT * FROM service_offers WHERE locale=? AND is_active=1 ORDER BY sort_order,id',[$locale]);echo '<!doctype html><html lang="'.Security::escape($locale).'" dir="'.($locale==='ar'?'rtl':'ltr').'"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Hizmet ve Fiyatlar</title><link rel="stylesheet" href="/assets/css/theme.css"><body><main class="container"><h1>Hizmet ve Fiyatlar</h1>';foreach($services as $service){echo '<section class="card"><h2>'.Security::escape($service['title']).'</h2>'.($service['summary']?'<p>'.Security::escape($service['summary']).'</p>':'').'<div class="grid">';foreach(App::db()->fetchAll('SELECT * FROM service_prices WHERE service_id=? ORDER BY is_featured DESC,sort_order,id',[(int)$service['id']]) as $price){echo '<article class="card"><h3>'.Security::escape($price['label']).'</h3><strong>'.number_format((float)$price['price'],2,',','.').' '.Security::escape($price['currency']).'</strong>'.($price['unit_label']?'<span> / '.Security::escape($price['unit_label']).'</span>':'').($price['note']?'<p>'.Security::escape($price['note']).'</p>':'').'</article>';}echo '</div></section>';}echo '</main></body></html>';
 }
}
