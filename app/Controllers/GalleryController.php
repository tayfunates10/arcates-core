<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\App;use Arcates\Core\Security;use Arcates\Core\WhatsApp;
final class GalleryController
{
 public function index(): void{$cats=App::db()->fetchAll('SELECT id,name,slug FROM gallery_categories ORDER BY name');echo '<!doctype html><html lang="tr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Portföy</title><link rel="stylesheet" href="/assets/css/theme.css"><body><main class="container"><h1>Portföy</h1>';foreach($cats as $c){echo '<section><h2>'.Security::escape($c['name']).'</h2><div class="gallery">';foreach(App::db()->fetchAll('SELECT title,image_path,alt_text FROM gallery_items WHERE category_id=? ORDER BY sort_order,id',[(int)$c['id']]) as $i){echo '<figure><img loading="lazy" src="'.Security::escape($i['image_path']).'" alt="'.Security::escape($i['alt_text']).'"><figcaption>'.Security::escape($i['title']).'</figcaption></figure>';}echo '</div></section>';}echo '</main>'.WhatsApp::button('Merhaba, portföyünüz hakkında bilgi almak istiyorum.').'</body></html>';}
}
