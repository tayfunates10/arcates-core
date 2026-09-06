<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\App;use Arcates\Core\Locale;use Arcates\Core\Security;
final class CatalogController
{
    public function index(string $locale='tr'): void
    {
        if(!Locale::valid($locale)){$locale='tr';}$rows=App::db()->fetchAll('SELECT id,name,slug,description,image_path,base_price FROM products WHERE status=\'published\' AND locale=? ORDER BY updated_at DESC',[$locale]);echo '<!doctype html><html lang="'.Security::escape($locale).'"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Ürünler</title><link rel="stylesheet" href="/assets/css/theme.css"><body><main class="container"><h1>Ürünler</h1><div class="grid">';foreach($rows as $p){echo '<article class="card">'.(!empty($p['image_path'])?'<img loading="lazy" src="'.Security::escape($p['image_path']).'" alt="'.Security::escape($p['name']).'">':'').'<h2><a href="/urun/'.Security::escape($locale).'/'.Security::escape($p['slug']).'">'.Security::escape($p['name']).'</a></h2><p>'.Security::escape(mb_substr(strip_tags((string)$p['description']),0,180)).'</p><strong>'.number_format((float)$p['base_price'],2,',','.').' TL</strong></article>'; }echo '</div></main></body></html>';
    }
    public function show(string $locale,string $slug): void
    {
        if(!Locale::valid($locale)){http_response_code(404);return;}$p=App::db()->fetch('SELECT * FROM products WHERE locale=? AND slug=? AND status=\'published\'',[$locale,$slug]);if(!$p){http_response_code(404);return;}$variants=App::db()->fetchAll('SELECT id,sku,name,price,stock FROM product_variants WHERE product_id=? AND is_active=1 ORDER BY id',[(int)$p['id']]);echo '<!doctype html><html lang="'.Security::escape($locale).'"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.Security::escape($p['name']).'</title><link rel="stylesheet" href="/assets/css/theme.css"><body><main class="container"><h1>'.Security::escape($p['name']).'</h1>'.(!empty($p['image_path'])?'<img src="'.Security::escape($p['image_path']).'" alt="'.Security::escape($p['name']).'">':'').'<p>'.nl2br(Security::escape((string)$p['description'])).'</p>';foreach($variants as $v){echo '<form class="card" method="post" action="/sepet/ekle">'.\Arcates\Core\Csrf::field().'<input type="hidden" name="variant_id" value="'.(int)$v['id'].'"><strong>'.Security::escape($v['name']).' · '.number_format((float)$v['price'],2,',','.').' TL</strong><p>Stok: '.(int)$v['stock'].'</p><label>Adet<input type="number" name="quantity" min="1" max="'.max(1,(int)$v['stock']).'" value="1"></label><button'.((int)$v['stock']<1?' disabled':'').'>Sepete ekle</button></form>';}echo '</main></body></html>';
    }
}
