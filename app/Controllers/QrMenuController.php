<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\App;use Arcates\Core\Locale;use Arcates\Core\Security;use Arcates\Services\QrCode;
final class QrMenuController
{
 public function show(string $slug,string $locale='tr'): void
 {
  if(!Locale::valid($locale))$locale='tr';$menu=App::db()->fetch('SELECT * FROM qr_menus WHERE slug=? AND is_active=1',[$slug]);if(!$menu){http_response_code(404);return;}$cats=App::db()->fetchAll('SELECT * FROM qr_menu_categories WHERE menu_id=? AND locale=? ORDER BY sort_order,id',[(int)$menu['id'],$locale]);echo '<!doctype html><html lang="'.Security::escape($locale).'" dir="'.($locale==='ar'?'rtl':'ltr').'"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.Security::escape($menu['name']).'</title><link rel="stylesheet" href="/assets/css/theme.css"><body><main class="container"><h1>'.Security::escape($menu['name']).'</h1><nav>';foreach(Locale::supported() as $l)echo '<a href="/menu/'.Security::escape($slug).'/'.Security::escape($l).'">'.Security::escape(strtoupper($l)).'</a> ';echo '</nav>';foreach($cats as $cat){echo '<section><h2>'.Security::escape($cat['name']).'</h2>';foreach(App::db()->fetchAll('SELECT * FROM qr_menu_items WHERE category_id=? AND is_active=1 ORDER BY sort_order,id',[(int)$cat['id']]) as $item){echo '<article class="card">'.($item['image_path']?'<img loading="lazy" src="'.Security::escape($item['image_path']).'" alt="'.Security::escape($item['name']).'">':'').'<h3>'.Security::escape($item['name']).'</h3><p>'.Security::escape((string)$item['description']).'</p><strong>'.number_format((float)$item['price'],2,',','.').' '.Security::escape($item['currency']).'</strong></article>';}echo '</section>';}echo '</main></body></html>';
 }
 public function qr(string $slug): void
 {
  $locale=(string)($_GET['locale']??'tr');if(!Locale::valid($locale))$locale='tr';$menu=App::db()->fetch('SELECT id FROM qr_menus WHERE slug=? AND is_active=1',[$slug]);if(!$menu){http_response_code(404);return;}$url=rtrim((string)App::config('app.url',''),'/').'/menu/'.rawurlencode($slug).'/'.rawurlencode($locale);$svg=QrCode::svg($url);header('Content-Type: image/svg+xml; charset=UTF-8');header('Cache-Control: public, max-age=3600');echo $svg;
 }
}
