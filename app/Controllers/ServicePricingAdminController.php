<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\AdminView;use Arcates\Core\App;use Arcates\Core\Csrf;use Arcates\Core\Locale;use Arcates\Core\Security;use Arcates\Core\Text;
final class ServicePricingAdminController
{
 public function index(): void
 {
  AdminView::header('Hizmet & Fiyat');$a=Security::escape((string)App::config('app.admin_path','yonetim'));echo '<form class="card" method="post" action="/'.$a.'/hizmet-fiyat/hizmet">'.Csrf::field().'<h2>Hizmet ekle</h2><select name="locale">';foreach(Locale::supported() as $l)echo '<option>'.Security::escape($l).'</option>';echo '</select><input name="title" required maxlength="190" placeholder="Hizmet"><input name="slug" maxlength="190" placeholder="slug"><input name="summary" maxlength="500" placeholder="Kısa açıklama"><button>Ekle</button></form>';
  foreach(App::db()->fetchAll('SELECT * FROM service_offers ORDER BY locale,sort_order,id') as $s){echo '<section class="card"><h2>'.Security::escape($s['locale'].' · '.$s['title']).'</h2><form method="post" action="/'.$a.'/hizmet-fiyat/fiyat">'.Csrf::field().'<input type="hidden" name="service_id" value="'.(int)$s['id'].'"><input name="label" required maxlength="190" placeholder="Paket / işlem"><input type="number" name="price" min="0" step="0.01" value="0"><input name="currency" maxlength="3" value="TRY"><input name="unit_label" maxlength="80" placeholder="seans / ay / kişi"><input name="note" maxlength="500" placeholder="Not"><label><input type="checkbox" name="is_featured" value="1"> Öne çıkar</label><button>Fiyat ekle</button></form>';foreach(App::db()->fetchAll('SELECT * FROM service_prices WHERE service_id=? ORDER BY is_featured DESC,sort_order,id',[(int)$s['id']]) as $p)echo '<p>'.Security::escape($p['label']).' · '.number_format((float)$p['price'],2,',','.').' '.Security::escape($p['currency']).($p['unit_label']?' / '.Security::escape($p['unit_label']):'').'</p>';echo '</section>';}AdminView::footer();
 }
 public function addService(): void
 {
  AdminView::requireUser();Csrf::requireValid($_POST['_csrf']??null);$locale=(string)($_POST['locale']??'tr');$title=trim((string)($_POST['title']??''));if(!Locale::valid($locale)||$title==='')throw new \RuntimeException('Hizmet bilgisi geçersiz.');$slug=Text::slug((string)($_POST['slug']??$title));App::db()->execute('INSERT INTO service_offers(locale,title,slug,summary,is_active,sort_order,created_at,updated_at) VALUES(?,?,?,?,1,0,NOW(),NOW())',[$locale,mb_substr($title,0,190),$slug,mb_substr(trim((string)($_POST['summary']??'')),0,500)?:null]);$this->back();
 }
 public function addPrice(): void
 {
  AdminView::requireUser();Csrf::requireValid($_POST['_csrf']??null);$label=trim((string)($_POST['label']??''));$currency=strtoupper(trim((string)($_POST['currency']??'TRY')));if($label===''||!preg_match('/^[A-Z]{3}$/',$currency))throw new \RuntimeException('Fiyat bilgisi geçersiz.');App::db()->execute('INSERT INTO service_prices(service_id,label,price,currency,unit_label,note,is_featured,sort_order,created_at) VALUES(?,?,?,?,?,?,?,0,NOW())',[(int)($_POST['service_id']??0),mb_substr($label,0,190),max(0,(float)($_POST['price']??0)),$currency,mb_substr(trim((string)($_POST['unit_label']??'')),0,80)?:null,mb_substr(trim((string)($_POST['note']??'')),0,500)?:null,($_POST['is_featured']??'')==='1'?1:0]);$this->back();
 }
 private function back(): void{header('Location: /'.App::config('app.admin_path','yonetim').'/hizmet-fiyat');}
}
