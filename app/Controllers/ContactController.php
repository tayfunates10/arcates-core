<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\App;use Arcates\Core\Csrf;use Arcates\Core\RateLimiter;use Arcates\Core\Security;use Arcates\Services\Mailer;
final class ContactController
{
 public function form(): void{echo '<!doctype html><html lang="tr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>İletişim</title><link rel="stylesheet" href="/assets/css/theme.css"><body><main class="container"><h1>İletişim</h1><form method="post">'.Csrf::field().'<label>Ad soyad<input name="name" required maxlength="190"></label><label>E-posta<input type="email" name="email" required maxlength="190"></label><label>Telefon<input name="phone" maxlength="50"></label><label>Mesaj<textarea name="message" required maxlength="5000"></textarea></label><div style="position:absolute;left:-9999px" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div><label><input type="checkbox" name="kvkk_consent" value="1" required> KVKK aydınlatma metnini okudum ve form verilerimin iletişim amacıyla işlenmesine açık rıza veriyorum.</label><button>Gönder</button></form><p><a href="/kvkk">KVKK Aydınlatma Metni</a> · <a href="/gizlilik">Gizlilik Politikası</a></p></main></body></html>';}
 public function submit(): void
 {
  Csrf::requireValid($_POST['_csrf']??null);if(trim((string)($_POST['website']??''))!==''){http_response_code(204);return;}$limiter=new RateLimiter(App::db());if(!$limiter->genericAllowed('contact:'.Security::clientIp(),3,600)){http_response_code(429);echo 'Çok sık gönderim.';return;}
  $name=trim((string)($_POST['name']??''));$email=filter_var($_POST['email']??'',FILTER_VALIDATE_EMAIL);$phone=trim((string)($_POST['phone']??''));$message=trim((string)($_POST['message']??''));$consent=($_POST['kvkk_consent']??'')==='1';if($name===''||$email===false||$message===''||!$consent){http_response_code(422);echo 'Zorunlu alanları ve açık rızayı kontrol edin.';return;}
  App::db()->execute('INSERT INTO contact_submissions(name,email,phone,message,kvkk_consent,created_at) VALUES(?,?,?,?,1,NOW())',[$name,$email,$phone,$message]);Mailer::contact(compact('name','email','phone','message'));echo '<p>Mesajınız alındı.</p>';
 }
}
