<?php
declare(strict_types=1);
namespace Arcates\Services;
use Arcates\Core\App;use Arcates\Core\Logger;
final class Mailer
{
 public static function contact(array $data): bool
 {
  $to=self::headerSafe((string)App::config('mail.to',''));if($to==='')return false;$subject='Yeni iletişim formu - '.(string)App::config('app.name','Arcates');$email=self::headerSafe((string)$data['email']);$body="Ad: {$data['name']}\nE-posta: {$email}\nTelefon: {$data['phone']}\n\n{$data['message']}";return self::send($to,$subject,$body,$email);
 }
 public static function reservation(array $data): bool
 {
  $to=self::headerSafe((string)($data['guest_email']??''));if($to==='')return false;$body="Kod: {$data['public_code']}\nBirim: {$data['unit_name']}\nBaşlangıç: {$data['starts_at']}\nBitiş: {$data['ends_at']}\nTutar: {$data['total_amount']} {$data['currency']}";return self::send($to,'Rezervasyon isteğiniz alındı',$body);
 }
 public static function orderCreated(array $order): bool
 {
  $to=self::headerSafe((string)($order['email']??''));if($to==='')return false;$body="Sipariş: {$order['public_code']}\nAra toplam: {$order['subtotal']} TL\nİndirim: {$order['discount_total']} TL\nKargo: {$order['shipping_total']} TL\nToplam: {$order['grand_total']} TL\nÖdeme durumu: {$order['payment_status']}";return self::send($to,'Siparişiniz oluşturuldu',$body);
 }
 public static function orderStatus(array $order): bool
 {
  $to=self::headerSafe((string)($order['email']??''));if($to==='')return false;$body="Sipariş: {$order['public_code']}\nSipariş durumu: {$order['status']}\nÖdeme durumu: {$order['payment_status']}\nToplam: {$order['grand_total']} TL";return self::send($to,'Sipariş durumunuz güncellendi',$body);
 }
 private static function send(string $to,string $subject,string $body,string $replyTo=''): bool
 {
  $headers=['From: '.self::headerSafe((string)App::config('mail.from','noreply@localhost')),'Content-Type: text/plain; charset=UTF-8'];if($replyTo!=='')$headers[]='Reply-To: '.self::headerSafe($replyTo);$ok=@mail(self::headerSafe($to),self::headerSafe($subject),$body,implode("\r\n",$headers));if(!$ok)Logger::error('Mail failed',['to'=>$to,'subject'=>$subject]);return $ok;
 }
 private static function headerSafe(string $value): string{return trim(str_replace(["\r","\n"],'',$value));}
}
