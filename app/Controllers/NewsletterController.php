<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\App;use Arcates\Core\Csrf;use Arcates\Core\RateLimiter;use Arcates\Core\Security;use Arcates\Services\NewsletterService;

final class NewsletterController
{
    public function form(): void{echo '<!doctype html><html lang="tr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>E-posta Bülteni</title><link rel="stylesheet" href="/assets/css/theme.css"><body><main class="container"><h1>E-posta Bülteni</h1><form method="post">'.Csrf::field().'<label>E-posta<input type="email" name="email" required maxlength="190" autocomplete="email"></label><div style="position:absolute;left:-9999px" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div><label><input type="checkbox" name="consent" value="1" required> Bültene abone olmak ve e-posta almak istiyorum.</label><button>Abone ol</button></form><p>Abonelik e-postadaki onay bağlantısına tıklanana kadar aktif olmaz.</p></main></body></html>';}
    public function subscribe(): void
    {
        Csrf::requireValid($_POST['_csrf']??null);if(trim((string)($_POST['website']??''))!==''){http_response_code(204);return;}$limiter=new RateLimiter(App::db());if(!$limiter->genericAllowed('newsletter:'.Security::clientIp(),5,3600)){http_response_code(429);echo 'Çok sık deneme.';return;}if(($_POST['consent']??'')!=='1'){http_response_code(422);echo 'Bülten onayı gerekli.';return;}try{(new NewsletterService())->subscribe((string)($_POST['email']??''));}catch(\RuntimeException $e){http_response_code(422);echo 'Geçerli e-posta adresi girin.';return;}echo '<p>Adres uygunsa onay e-postası gönderildi. Aboneliği e-postadaki bağlantıyla doğrulayın.</p>';
    }
    public function confirm(): void{$ok=(new NewsletterService())->confirm((string)($_GET['token']??''));http_response_code($ok?200:400);echo $ok?'<p>Aboneliğiniz onaylandı.</p>':'<p>Onay bağlantısı geçersiz veya süresi dolmuş.</p>';}
    public function unsubscribe(): void{$ok=(new NewsletterService())->unsubscribe((int)($_GET['id']??0),(string)($_GET['sig']??''));http_response_code($ok?200:400);echo $ok?'<p>Bülten aboneliğiniz sonlandırıldı.</p>':'<p>Ayrılma bağlantısı geçersiz.</p>';}
}
