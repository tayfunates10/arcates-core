<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\AdminView;use Arcates\Core\App;use Arcates\Core\Csrf;use Arcates\Core\Security;use Arcates\Services\NewsletterService;

final class NewsletterAdminController
{
    public function index(): void
    {
        AdminView::header('E-posta Bülteni');$a=Security::escape((string)App::config('app.admin_path','yonetim'));$count=App::db()->fetch("SELECT COUNT(*) n FROM newsletter_subscribers WHERE status='active'");echo '<section class="card"><h2>'.(int)($count['n']??0).' aktif abone</h2><form method="post" action="/'.$a.'/bulten/kampanya">'.Csrf::field().'<input name="subject" required maxlength="190" placeholder="Konu"><textarea name="body_text" required maxlength="50000" rows="12" placeholder="Düz metin bülten içeriği"></textarea><button>Taslak oluştur</button></form></section>';foreach(App::db()->fetchAll('SELECT c.*,(SELECT COUNT(*) FROM newsletter_deliveries d WHERE d.campaign_id=c.id) recipients,(SELECT COUNT(*) FROM newsletter_deliveries d WHERE d.campaign_id=c.id AND d.status=\'sent\') sent_count FROM newsletter_campaigns c ORDER BY c.id DESC LIMIT 100') as $c){echo '<article class="card"><strong>'.Security::escape($c['subject']).'</strong><p>Durum: '.Security::escape($c['status']).' · Alıcı: '.(int)$c['recipients'].' · Gönderilen: '.(int)$c['sent_count'].'</p>';if((string)$c['status']==='draft')echo '<form method="post" action="/'.$a.'/bulten/kuyruk">'.Csrf::field().'<input type="hidden" name="id" value="'.(int)$c['id'].'"><button>Aktif aboneleri kuyruğa al</button></form>';echo '</article>';}AdminView::footer();
    }
    public function campaign(): void{AdminView::requireUser();Csrf::requireValid($_POST['_csrf']??null);$subject=trim((string)($_POST['subject']??''));$body=trim((string)($_POST['body_text']??''));if($subject===''||mb_strlen($subject)>190||$body===''||mb_strlen($body)>50000)throw new \RuntimeException('Kampanya içeriği geçersiz.');App::db()->execute("INSERT INTO newsletter_campaigns(subject,body_text,status,created_at,updated_at) VALUES(?,?,'draft',NOW(),NOW())",[$subject,$body]);$this->back();}
    public function queue(): void{AdminView::requireUser();Csrf::requireValid($_POST['_csrf']??null);(new NewsletterService())->queue((int)($_POST['id']??0));$this->back();}
    private function back(): void{header('Location: /'.App::config('app.admin_path','yonetim').'/bulten');}
}
