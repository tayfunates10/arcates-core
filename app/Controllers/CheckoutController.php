<?php
declare(strict_types=1);
namespace Arcates\Controllers;
use Arcates\Core\Csrf;use Arcates\Services\Cart;use Arcates\Services\Mailer;use Arcates\Services\OrderService;
final class CheckoutController
{
    public function form(): void
    {
        if(!Cart::raw()){header('Location: /sepet');return;}echo '<!doctype html><html lang="tr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Ödeme</title><link rel="stylesheet" href="/assets/css/theme.css"><body><main class="container"><h1>Teslimat ve ödeme</h1><form class="card" method="post" action="/odeme">'.Csrf::field().'<label>Ad soyad<input name="name" required maxlength="190"></label><label>E-posta<input type="email" name="email" required maxlength="190"></label><label>Telefon<input name="phone" required maxlength="50"></label><label>Adres<textarea name="address" required maxlength="2000"></textarea></label><label>Şehir<input name="city" required maxlength="120"></label><label>Posta kodu<input name="postal_code" maxlength="30"></label><label>Kupon<input name="coupon" maxlength="80"></label><label><input type="checkbox" name="sales_terms" value="1" required> Mesafeli satış ve iade koşullarını okudum.</label><button>Siparişi oluştur ve ödemeye geç</button></form><p><a href="/mesafeli-satis">Mesafeli satış</a> · <a href="/iade">İade koşulları</a></p></main></body></html>';
    }
    public function submit(): void
    {
        Csrf::requireValid($_POST['_csrf']??null);if(($_POST['sales_terms']??'')!=='1')throw new \RuntimeException('Sözleşme onayı gerekli.');$name=trim((string)($_POST['name']??''));$email=trim((string)($_POST['email']??''));$phone=trim((string)($_POST['phone']??''));$address=trim((string)($_POST['address']??''));$city=trim((string)($_POST['city']??''));if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||$phone===''||$address===''||$city==='')throw new \RuntimeException('Teslimat bilgileri eksik.');$order=OrderService::create(['name'=>mb_substr($name,0,190),'email'=>mb_substr($email,0,190),'phone'=>mb_substr($phone,0,50),'address'=>mb_substr($address,0,2000),'city'=>mb_substr($city,0,120),'postal_code'=>mb_substr(trim((string)($_POST['postal_code']??'')),0,30)],Cart::raw(),strtoupper(trim((string)($_POST['coupon']??'')))?:null);Cart::clear();Mailer::orderCreated($order);header('Location: /odeme/baslat?order='.rawurlencode((string)$order['public_code']));
    }
}
