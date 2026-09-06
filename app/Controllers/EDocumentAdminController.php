<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\AdminView;use Arcates\Core\App;use Arcates\Core\Csrf;use Arcates\Core\Security;use Arcates\Services\EDocumentService;

final class EDocumentAdminController
{
    public function index(): void
    {
        AdminView::header('e-Fatura / e-Arşiv');$a=Security::escape((string)App::config('app.admin_path','yonetim'));$orders=App::db()->fetchAll('SELECT id,public_code,customer_name,identity_number,grand_total FROM orders WHERE payment_status=\'paid\' AND status<>\'cancelled\' ORDER BY id DESC');echo '<section class="card"><h2>UBL-TR belge hazırla</h2><p>Arcates vergi oranı veya yasal UBL üretmez. Entegratöre gönderilecek geçerli UBL-TR XML'i buraya girin.</p><form method="post" action="/'.$a.'/e-belge/hazirla">'.Csrf::field().'<select name="order_id" required>';foreach($orders as $o)echo '<option value="'.(int)$o['id'].'">'.Security::escape($o['public_code'].' · '.$o['customer_name'].' · '.$o['identity_number']).'</option>';echo '</select><input name="recipient_alias" maxlength="255" placeholder="Posta kutusu alias (e-Fatura için opsiyonel)"><textarea name="ubl_xml" required rows="14" spellcheck="false" placeholder="<?xml version=...><Invoice ...>...</Invoice>"></textarea><button>Belgeyi hazırla</button></form></section>';
        echo '<section class="card"><form method="post" action="/'.$a.'/e-belge/kontrol">'.Csrf::field().'<button>Bekleyen durumları kontrol et</button></form></section>';foreach(App::db()->fetchAll('SELECT d.*,o.public_code,o.customer_name,o.grand_total FROM e_documents d JOIN orders o ON o.id=d.order_id ORDER BY d.id DESC') as $d){echo '<article class="card"><strong>'.Security::escape($d['public_code'].' · '.$d['customer_name']).'</strong><p>'.Security::escape(strtoupper((string)$d['document_type']).' · '.$d['profile_id'].' · '.$d['status']).'</p><p>UUID: '.Security::escape((string)($d['external_uuid']??'-')).' · No: '.Security::escape((string)($d['external_number']??'-')).'</p>'.($d['last_error']?'<p>Hata: '.Security::escape($d['last_error']).'</p>':'');if(in_array((string)$d['status'],['prepared','failed'],true))echo '<form method="post" action="/'.$a.'/e-belge/gonder">'.Csrf::field().'<input type="hidden" name="id" value="'.(int)$d['id'].'"><button>Entegratöre gönder</button></form>';echo '</article>';}AdminView::footer();
    }
    public function prepare(): void{AdminView::requireUser();Csrf::requireValid($_POST['_csrf']??null);$order=(int)($_POST['order_id']??0);$xml=(string)($_POST['ubl_xml']??'');$alias=(string)($_POST['recipient_alias']??'');if($order<1)throw new \RuntimeException('Sipariş seçimi gerekli.');(new EDocumentService())->prepare($order,$xml,$alias);$this->back();}
    public function send(): void{AdminView::requireUser();Csrf::requireValid($_POST['_csrf']??null);$id=(int)($_POST['id']??0);if($id<1)throw new \RuntimeException('e-Belge seçimi gerekli.');(new EDocumentService())->send($id);$this->back();}
    public function check(): void{AdminView::requireUser();Csrf::requireValid($_POST['_csrf']??null);(new EDocumentService())->checkPending(20);$this->back();}
    private function back(): void{header('Location: /'.App::config('app.admin_path','yonetim').'/e-belge');}
}
