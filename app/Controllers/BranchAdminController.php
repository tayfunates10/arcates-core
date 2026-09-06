<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\AdminView;use Arcates\Core\App;use Arcates\Core\Csrf;use Arcates\Core\Locale;use Arcates\Core\Security;use Arcates\Core\Text;
final class BranchAdminController
{
 public function index(): void
 {
  AdminView::header('Şubeler');$a=Security::escape((string)App::config('app.admin_path','yonetim'));echo '<form class="card" method="post" action="/'.$a.'/subeler/ekle">'.Csrf::field().'<h2>Şube ekle</h2><select name="locale">';foreach(Locale::supported() as $l)echo '<option>'.Security::escape($l).'</option>';echo '</select><input name="name" required maxlength="190" placeholder="Şube adı"><input name="slug" maxlength="190" placeholder="slug"><input name="phone" maxlength="50" placeholder="Telefon"><input type="email" name="email" maxlength="190" placeholder="E-posta"><input name="address" required maxlength="500" placeholder="Adres"><input name="city" required maxlength="120" placeholder="Şehir"><input name="district" maxlength="120" placeholder="İlçe"><input type="number" step="0.0000001" name="latitude" placeholder="Enlem"><input type="number" step="0.0000001" name="longitude" placeholder="Boylam"><input name="opening_hours" maxlength="500" placeholder="Çalışma saatleri"><button>Ekle</button></form>';
  foreach(App::db()->fetchAll('SELECT * FROM branches ORDER BY locale,sort_order,id') as $b){echo '<section class="card"><h2>'.Security::escape($b['locale'].' · '.$b['name']).'</h2><p>'.Security::escape($b['address'].' · '.$b['city']).'</p><form method="post" action="/'.$a.'/subeler/hizmet">'.Csrf::field().'<input type="hidden" name="branch_id" value="'.(int)$b['id'].'"><input name="service_name" required maxlength="190" placeholder="Şubede sunulan hizmet"><button>Hizmet ekle</button></form>';foreach(App::db()->fetchAll('SELECT service_name FROM branch_services WHERE branch_id=? ORDER BY sort_order,id',[(int)$b['id']]) as $s)echo '<span class="tag">'.Security::escape($s['service_name']).'</span>';echo '</section>';}AdminView::footer();
 }
 public function add(): void
 {
  AdminView::requireUser();Csrf::requireValid($_POST['_csrf']??null);$locale=(string)($_POST['locale']??'tr');$name=trim((string)($_POST['name']??''));$address=trim((string)($_POST['address']??''));$city=trim((string)($_POST['city']??''));if(!Locale::valid($locale)||$name===''||$address===''||$city==='')throw new \RuntimeException('Şube bilgileri eksik.');$email=trim((string)($_POST['email']??''));if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))throw new \RuntimeException('E-posta geçersiz.');$lat=$this->coordinate($_POST['latitude']??null,-90,90);$lon=$this->coordinate($_POST['longitude']??null,-180,180);if(($lat===null)!==($lon===null))throw new \RuntimeException('Enlem ve boylam birlikte girilmelidir.');App::db()->execute('INSERT INTO branches(locale,name,slug,phone,email,address,city,district,latitude,longitude,opening_hours,is_active,sort_order,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,1,0,NOW(),NOW())',[$locale,mb_substr($name,0,190),Text::slug((string)($_POST['slug']??$name)),mb_substr(trim((string)($_POST['phone']??'')),0,50)?:null,$email?:null,mb_substr($address,0,500),mb_substr($city,0,120),mb_substr(trim((string)($_POST['district']??'')),0,120)?:null,$lat,$lon,mb_substr(trim((string)($_POST['opening_hours']??'')),0,500)?:null]);$this->back();
 }
 public function addService(): void
 {
  AdminView::requireUser();Csrf::requireValid($_POST['_csrf']??null);$name=trim((string)($_POST['service_name']??''));if($name==='')throw new \RuntimeException('Hizmet adı gerekli.');App::db()->execute('INSERT INTO branch_services(branch_id,service_name,sort_order,created_at) VALUES(?,?,0,NOW())',[(int)($_POST['branch_id']??0),mb_substr($name,0,190)]);$this->back();
 }
 private function coordinate(mixed $value,float $min,float $max): ?float{$value=trim((string)$value);if($value==='')return null;if(!is_numeric($value))throw new \RuntimeException('Koordinat geçersiz.');$number=(float)$value;if($number<$min||$number>$max)throw new \RuntimeException('Koordinat aralık dışında.');return $number;}
 private function back(): void{header('Location: /'.App::config('app.admin_path','yonetim').'/subeler');}
}
