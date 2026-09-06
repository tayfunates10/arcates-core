<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\AdminView;use Arcates\Core\App;use Arcates\Core\Csrf;use Arcates\Core\Security;
final class B2BAdminController
{
    public function index(): void{AdminView::header('Bayi hesapları');$a=Security::escape((string)App::config('app.admin_path','yonetim'));echo '<form class="card" method="post" action="/'.$a.'/bayiler/ekle">'.Csrf::field().'<label>Firma<input name="company" required></label><label>E-posta<input type="email" name="email" required></label><label>Şifre<input type="password" name="password" minlength="10" required></label><label>İskonto %<input type="number" step="0.01" min="0" max="100" name="discount" value="0"></label><button>Hesap ekle</button></form>';foreach(App::db()->fetchAll('SELECT company_name,email,discount_percent,is_active FROM b2b_accounts ORDER BY id DESC') as $r)echo '<p>'.Security::escape($r['company_name'].' · '.$r['email']).' · %'.Security::escape((string)$r['discount_percent']).'</p>';AdminView::footer();}
    public function add(): void{AdminView::requireUser();Csrf::requireValid($_POST['_csrf']??null);$company=trim((string)($_POST['company']??''));$email=mb_strtolower(trim((string)($_POST['email']??'')));$password=(string)($_POST['password']??'');if($company===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<10)throw new \RuntimeException('Bayi bilgileri geçersiz.');App::db()->execute('INSERT INTO b2b_accounts(company_name,email,password_hash,discount_percent,is_active,created_at) VALUES(?,?,?,?,1,NOW())',[mb_substr($company,0,190),mb_substr($email,0,190),password_hash($password,PASSWORD_DEFAULT),min(100,max(0,(float)($_POST['discount']??0)))]);header('Location: /'.App::config('app.admin_path','yonetim').'/bayiler');}
}
