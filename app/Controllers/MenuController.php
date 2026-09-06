<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\AdminView;use Arcates\Core\App;use Arcates\Core\Csrf;use Arcates\Core\Locale;use Arcates\Core\Security;
final class MenuController
{
 public function index(): void{AdminView::header('Menüler');echo '<form class="card" method="post">'.Csrf::field().'<div class="grid two"><label>Dil<select name="locale">';foreach(Locale::supported() as $l){echo '<option>'.Security::escape($l).'</option>';}echo '</select></label><label>Sıra<input type="number" name="sort_order" value="0"></label></div><label>Etiket<input name="label" required></label><label>URL<input name="url" required></label><button>Ekle</button></form>';foreach(App::db()->fetchAll('SELECT * FROM menus ORDER BY locale, sort_order, id') as $m){echo '<div class="card"><strong>'.Security::escape($m['locale'].' · '.$m['label']).'</strong> '.Security::escape($m['url']).'<form method="post" action="/'.Security::escape((string)App::config('app.admin_path','yonetim')).'/menuler/sil">'.Csrf::field().'<input type="hidden" name="id" value="'.(int)$m['id'].'"><button data-confirm="Menü silinsin mi?">Sil</button></form></div>';}AdminView::footer();}
 public function add(): void{AdminView::requireUser();Csrf::requireValid($_POST['_csrf']??null);$l=(string)($_POST['locale']??'tr');if(!Locale::valid($l))throw new \RuntimeException('Geçersiz dil.');App::db()->execute('INSERT INTO menus(locale,label,url,sort_order,created_at) VALUES(?,?,?,?,NOW())',[$l,trim((string)$_POST['label']),trim((string)$_POST['url']),(int)($_POST['sort_order']??0)]);header('Location: /'.App::config('app.admin_path','yonetim').'/menuler');}
 public function delete(): void{AdminView::requireUser();Csrf::requireValid($_POST['_csrf']??null);App::db()->execute('DELETE FROM menus WHERE id=?',[(int)($_POST['id']??0)]);header('Location: /'.App::config('app.admin_path','yonetim').'/menuler');}
}
