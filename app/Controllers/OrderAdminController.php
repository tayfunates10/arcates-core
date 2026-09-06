<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\AdminView;use Arcates\Core\App;use Arcates\Core\Csrf;use Arcates\Core\Security;use Arcates\Services\Mailer;use Arcates\Services\OrderService;
final class OrderAdminController
{
    public function index(): void{AdminView::header('Siparişler');$a=Security::escape((string)App::config('app.admin_path','yonetim'));foreach(App::db()->fetchAll('SELECT * FROM orders ORDER BY created_at DESC LIMIT 200') as $o){echo '<article class="card"><strong>'.Security::escape($o['public_code'].' · '.$o['customer_name']).'</strong><p>'.Security::escape($o['email'].' · ödeme '.$o['payment_status'].' · durum '.$o['status']).' · '.number_format((float)$o['grand_total'],2,',','.').' TL</p><form method="post" action="/'.$a.'/siparisler/durum">'.Csrf::field().'<input type="hidden" name="id" value="'.(int)$o['id'].'"><select name="status">';foreach(['pending','confirmed','preparing','shipped','completed','cancelled'] as $s)echo '<option'.($o['status']===$s?' selected':'').'>'.$s.'</option>';echo '</select><button>Güncelle</button></form></article>';}AdminView::footer();}
    public function status(): void{AdminView::requireUser();Csrf::requireValid($_POST['_csrf']??null);$id=(int)($_POST['id']??0);OrderService::setStatus($id,(string)($_POST['status']??''));$order=App::db()->fetch('SELECT * FROM orders WHERE id=?',[$id]);if($order)Mailer::orderStatus($order);header('Location: /'.App::config('app.admin_path','yonetim').'/siparisler');}
}
