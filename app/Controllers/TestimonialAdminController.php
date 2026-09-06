<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\AdminView;use Arcates\Core\App;use Arcates\Core\Csrf;use Arcates\Core\Security;
final class TestimonialAdminController
{
 public function index(): void{AdminView::header('Referanslar');$a=Security::escape((string)App::config('app.admin_path','yonetim'));echo '<form class="card" method="post" action="/'.$a.'/referanslar/ekle">'.Csrf::field().'<label>Ad<input name="name" required></label><label>Firma<input name="company"></label><label>Yorum<textarea name="quote" required></textarea></label><label>Sıra<input type="number" name="sort_order" value="0"></label><label><input type="checkbox" name="published" value="1"> Yayında</label><button>Ekle</button></form>';foreach(App::db()->fetchAll('SELECT * FROM testimonials ORDER BY sort_order,id') as $t)echo '<article class="card"><strong>'.Security::escape($t['name']).'</strong><p>'.Security::escape($t['quote']).'</p><form method="post" action="/'.$a.'/referanslar/sil">'.Csrf::field().'<input type="hidden" name="id" value="'.(int)$t['id'].'"><button data-confirm="Referans silinsin mi?">Sil</button></form></article>';AdminView::footer();}
 public function add(): void{AdminView::requireUser();Csrf::requireValid($_POST['_csrf']??null);$name=trim((string)($_POST['name']??''));$quote=trim((string)($_POST['quote']??''));if($name===''||$quote==='')throw new \RuntimeException('Ad ve yorum gerekli.');App::db()->execute('INSERT INTO testimonials(name,company,quote,is_published,sort_order,created_at) VALUES(?,?,?,?,?,NOW())',[$name,trim((string)($_POST['company']??'')),$quote,($_POST['published']??'')==='1'?1:0,(int)($_POST['sort_order']??0)]);$this->back();}
 public function delete(): void{AdminView::requireUser();Csrf::requireValid($_POST['_csrf']??null);App::db()->execute('DELETE FROM testimonials WHERE id=?',[(int)($_POST['id']??0)]);$this->back();}
 private function back(): void{header('Location: /'.App::config('app.admin_path','yonetim').'/referanslar');}
}
